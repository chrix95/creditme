<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UtilityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Apis\UserControllerExtension;

class TVController extends Controller
{

    public function __construct(UtilityController $utility, UserControllerExtension $user)
    {
        $this->utility = $utility;
        $this->user = $user;
    }

    public function getTVInfo($providerID)
    {
        $bundles = \App\TVBundle::where('service_id', $providerID)->orderBy('amount', 'asc')->get();

        if (count($bundles) <= 0) {
            return response()->json([
                'status'    =>  -1,
                'message'   =>  "No bundle for the selected provider"
            ]);
        }

        return response()->json([
            'status'    =>  '00',
            'bundles'   =>  $bundles
        ], 200);
    }

    public function getCardInfo(Request $request) {
        $serviceCode    = 0;
        $respFormat     = 'JSON';
        $package        = '';
        $provider       = '';
        
        $field_name = 'data';
        $status = '00';

        $resp = array(
            'msg'       =>  'Pending',
        );

        $data = array(
            'user_id'       =>  $request->user_id,
            'platform'      =>  $request->platform,
            'service_id'    =>  $request->service_id, // 21 - DSTV, 22 - GOTV, 23 - StarTimes
            'service_code'  =>  $request->service_code,
            'smartcard_num' =>  $request->smartcard_num,
            'amount'        =>  $request->amount,
            'email'         =>  $request->email,
            'phone'         =>  $request->phone,
            'passcode'      =>  $request->passcode
        );

        $validator = \Validator::make($data, [
            'platform'      =>  'required|string',
            'service_id'    =>  'required|numeric',
            'smartcard_num' =>  'required|numeric',
            'phone'         =>  'required|digits:11',
            'passcode'      =>  'required|string'
        ]);
        if($validator->fails()) {
            $status = '05';
            return $this->utility->response($status, 'error', $validator->errors());
        }
        // additional validation
        if ($data['service_id'] == 23) {
            $validator = \Validator::make($data, [
                'amount'    =>  'required|numeric'
            ]);
        } else {
            $validator = \Validator::make($data, [
                'service_code'    =>  'required|numeric'
            ]);
        }
        if($validator->fails()) {
            $status = '05';
            return $this->utility->response($status, 'error', $validator->errors());
        }

        if ($this->utility->verifyAPIPasscode($data['smartcard_num']) !== $request->passcode) {
            $status = '01';
        } else {
            $service = \App\Service::find($data['service_id']);
            if(count((array)$service) > 0) {
                $provider = $service->name;
                if($data['service_id'] == 23) {
                    // StarTimes
                    $bundleID = \App\TVBundle::where('code', 'StarTimes')->first();
                    $data['tv_bundles_id'] = $bundleID->id;
                    $package = "StarTimes";
                    $serviceCode = 'StarTimes';
                } else {
                    $bundleID = \App\TVBundle::where('code', $data['service_code'])->first();
                    if (!$bundleID) {
                        $status = '19';
                        $resp['status'] = -1;
                        $resp['msg'] = 'Package not found.';
                        return $this->utility->response($status, $field_name, $resp);
                    } else {
                        $data['tv_bundles_id'] = $bundleID->id;
                        $package = $bundleID->name;
                        $serviceCode = $bundleID->code;
                        $data['amount'] = $bundleID->amount;
                    }
                }
    
                $transactionID = $this->utility->generateTransactionID(4);
                $data['transaction_id'] = $transactionID;
                $data['status'] = 0;
                $data['amount_paid'] = 0.00;
                $data['commission'] = $service->commission;
                $data['payment_method'] = '';
                $data['payment_ref'] = 'Pending';
                $data['bundle_name'] = $package;
                $data['transaction_trials'] = 1;
    
                $apiString = $this->utility->generateTVAPIString(substr($transactionID, -12), $provider, $data['smartcard_num'], $serviceCode, env('MODE'));
    
                $hash = $this->utility->hashAPIString($apiString, env('MODE'));
    
                $cardInfo = $this->utility->getTVCardInfo(substr($transactionID, -12), $data['smartcard_num'], $serviceCode, $provider, $hash, $respFormat, env('MODE'));
    
                if($cardInfo['status'] === 1) {
                    $customerData = $cardInfo['msg'];
                    $data['access_token'] = $customerData['access_token'];
                    $data['customer_name'] = $customerData['customer'];
                    
                    // save this transaction.
                    $transaction = \App\TVTransaction::create($data);
                    // $amountToPay = intval($data['amount']) + intval($service->service_charge);
                    $amountToPay = intval($data['amount']);
                    $resp['trans_id'] = $transactionID;
                    $service = '';
                    if ($data['service_id'] == 21) {
                        $service = "DSTV";
                    } else if ($data['service_id'] == 22) {
                        $service = "GOTV";
                    } else {
                        $service = "StarTimes";
                    }
                    $resp['msg'] = array('customerName' => $customerData['customer'], 'customerNumber' => $customerData['customer_number'], 'transactionID' => $transactionID, 'amountToPay' => $amountToPay, 'access_token' => $customerData['access_token'], 'service' => $service, 'bundle_name' =>  $data['bundle_name']);
                    $status = '00';
                } else {
                    $status = '18';
                    $resp['status'] = -1;
                    $resp['msg'] = 'Error while getting smartcard info.';
                }
            } else {
                $status = '16';
                $resp['status'] = -1;
                $resp['msg'] = 'Service not found.';
            }
        }

        return $this->utility->response($status, $field_name, $resp);
    }

    public function request(Request $request) {
        $status = '';
        $field_name = 'data';
        $resp = array();

        $data = array(
            'payment_method'    =>  $request->payment_method,
            'payment_ref'       =>  $request->payment_ref,
            'transaction_id'    =>  $request->transaction_id,
            'access_token'      =>  $request->access_token,
            'passcode'          =>  $request->passcode
        );

        $validator = \Validator::make($data, [
            'payment_method'   =>  'required|string',
            'payment_ref'      =>  'string',
            'transaction_id'   =>  'required|string',
            'access_token'     =>  'required|string',
            'passcode'         =>  'required|string'
        ]);

        if($validator->fails()) {
            $status = '05';
            return $this->utility->response($status, 'error', $validator->errors());
        }

        if ($this->utility->verifyAPIPasscode($data['access_token']) !== $request->passcode) {
            $status = '01';
        } else {
            // start the main implementation
            $tvTransaction = \App\TVTransaction::where('access_token', $data['access_token'])->first();
            if(count((array)$tvTransaction) <= 0) {
                $status = '07';
            } else {
                // start all form of TV vend depending on the payment method
                if($data['payment_method'] === "WALLET") {
                    // Wallet transaction
                    $user = \App\User::find($tvTransaction->user_id);
                    if ($user) {
                        $current_balance = $user->wallet->balance;
                        if ($current_balance >= $tvTransaction->amount) {
                            // deduct user wallet
                            $this->updateUserWallet($user, $tvTransaction, 2);
                            // update transation status
                            $this->updateTransactionInfo($tvTransaction, $data);
                            // oh yeah we got the transaction details. Simply parse the transaction details to the power vending event handler to dispense the token.
                            $this->utility->handleTV($tvTransaction);
                            // as of now the event listener must have updated transaction status in the database so go get it.
                            $updatedTransaction = \App\PowerTransaction::find($tvTransaction->id);
                            if($updatedTransaction->status != 2) {
                                // something must have gone wrong while trying to dispense.
                                $status = '11';
                                $resp['status'] = -1;
                                $resp['msg'] = 'Failed.';
                                $this->updateUserWallet($user, $tvTransaction, 1);
                            } else {
                                $status = '00';
                                $resp['token'] = $updatedTransaction->token;
                                $resp['units'] = $updatedTransaction->units;
                            }
                        } else {
                            $status = '20';
                        }
                    } else {
                        $status = '21';
                    }
                } else if ($data['payment_method'] === "VOUCHER") {
                    // voucher transaction
                    $voucher_details = \App\Voucher::where('voucher', $data['payment_ref'])->where('expiry', '>', now())->first();
                    if($voucher_details) {
                        if ($voucher_details->balance >= $tvTransaction->amount) {
                            $this->updateTransactionInfo($tvTransaction, $data);
                            // oh yeah we got the transaction details. Simply parse the transaction details to the power vending event handler to dispense the token.
                            $this->utility->handleTV($tvTransaction);
                            // as of now the event listener must have updated transaction status in the database so go get it.
                            $updatedTransaction = \App\TvTransaction::find($tvTransaction->id);
                            if($updatedTransaction->status != 2) {
                                // something must have gone wrong while trying to dispense.
                                $status = '11';
                                $resp['status'] = -1;
                                $resp['msg'] = 'Failed.';
                            } else {
                                $status = '00';
                                $voucher_details->update(['balance' => ($voucher_details->balance - $tvTransaction->amount)]);
                            }
                        } else {
                            $status = '14';
                            $resp['message'] = "Voucher balance is " . $voucher_details->balance . ". Kindly select a different payment method.";
                        }
                    } else {
                        $status = '13';
                        $resp['message'] = "Invalid voucher code/voucher has expired. Kindly select a different payment method.";
                    }
                } else if ($data['payment_method'] === "CARD") {
                    if(env('PAYMENT_MODE') == 1) {
                        $verifyPayment = $this->utility->verifyPayment($data['payment_ref'], 'tv', $tvTransaction->amount, $tvTransaction->id, env('MODE'));
                    } else if(env('PAYMENT_MODE') == 2) {
                        $verifyPayment = $this->utility->verifyRavePayment($data['payment_ref'], 'tv', $tvTransaction->amount, $tvTransaction->id, env('MODE'));
                    } else {
                        $verifyPayment = $this->utility->verifyGladePayment($data['payment_ref'], 'tv', $tvTransaction->amount, $tvTransaction->id,  env('MODE'));
                    }
                    if($verifyPayment == -1) {
                        $status = '10';
                        $resp['status'] = $verifyPayment;
                        $resp['msg'] = 'We were unable to initiate the process of verifying your payment status. Please contact our customer support lines with your transaction reference for help.';
                    } else if($verifyPayment == 419) {
                        $status = '08';
                        $resp['status'] = $verifyPayment;
                        $resp['msg'] = 'Unfortunately, our servers encountered an error trying to validate your payment status. Please contact our customer support lines with your transaction reference for help.';
                    } else if($verifyPayment == 404) {
                        $status = '09';
                        $resp['status'] = $verifyPayment;
                        $resp['msg'] = 'We could not find your payment transaction reference. Your payment might have been declined. Please contact our customer support lines with your transaction reference for help.';
                    } else if($verifyPayment == '503') {
                        $status = '10';
                        $resp['status'] = $verifyPayment;
                        $resp['msg'] = 'Unable to verify transaction. Please contact our customer support lines with your transaction reference for help.';
                    } else if($verifyPayment == 100) {
                        // since the users payment went through, update the payment reference.
                        $this->updateTransactionInfo($tvTransaction, $data);
                        // oh yeah we got the transaction details. Simply parse the transaction details to the power vending event handler to dispense the token.
                        $this->utility->handleTV($tvTransaction);
                        $updatedTransaction = \App\TVTransaction::where('access_token', $data['access_token'])->first();
                        if($updatedTransaction->status != 2) {
                            // something must have gone wrong while trying to dispense.
                            $status = '11';
                            $resp['status'] = -1;
                            $resp['msg'] = 'Failed.';
                        } else {
                            $status = '00';
                        }
                    } else {
                        $status = '10';
                    }
                } else {
                    // invalid payment method
                    $status = '17';
                }
            }
        }
        return $this->utility->response($status, $field_name, $resp);
    }

    public function updateTransactionInfo($tvTransaction, $data) {
        $tvTransaction->update([
            'status'            =>  1,
            'payment_ref'       =>  $data['payment_ref'],
            'amount_paid'       =>  $tvTransaction->amount,
            'payment_method'    =>  $data['payment_method'],
        ]);
    }

    public function updateUserWallet($user, $tvTransaction, $transaction_type) {
        // 1: credit; 2: debit
        $tvBundle = \App\TVBundle::find($tvTransaction->tv_bundles_id);
        $current_balance = $user->wallet->balance;
        if ($transaction_type == 1) {
            $new_balance = floatval($current_balance) + floatval($tvTransaction->amount);
        } else {
            $new_balance = floatval($current_balance) - floatval($tvTransaction->amount);
        }
        $user->wallet()->update(['balance' => $new_balance]);
        $description = $tvBundle->name . ' N ' . $tvTransaction->amount . ' to ' . $tvTransaction->smartcard_num;
        $this->user->log_wallet_transaction($user, $tvTransaction->amount, $new_balance, $transaction_type, $description, 1, $tvTransaction->transaction_id);
    }

}
