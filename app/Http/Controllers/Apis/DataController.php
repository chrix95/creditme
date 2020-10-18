<?php

namespace App\Http\Controllers\Apis;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\Apis\UserControllerExtension;

class DataController extends Controller
{
    public $utility;
    public function __construct(UtilityController $utility, UserControllerExtension $user) {
        $this->utility = $utility;
        $this->user = $user;
    }

    public function registerAttempt(Request $request) {
        $resp = array();
        $status = '00';
        $field_name = 'data';
        $data = array(
            'phone'             =>  $request->phone,
            'email'             =>  $request->email,
            'service_id'        =>  $request->service_id,
            'data_bundles_id'   =>  $request->data_bundles_id,
            'user_id'           =>  $request->user_id,
            'platform'          =>  $request->platform,
            'passcode'          =>  $request->passcode
        );

        $validator = \Validator::make($data, [
            'phone'             =>  'required|digits:11',
            'email'             =>  'email',
            'service_id'        =>  'required|numeric',
            'data_bundles_id'   =>  'required|numeric',
            'platform'          =>  'required|string',
            'passcode'          =>  'required|string'
        ]);

        if($validator->fails()) {
            $status = '05';
            return $this->utility->response($status, 'error', $validator->errors());
        }

        if ($this->utility->verifyAPIPasscode($data['phone']) !== $request->passcode) {
            $status = '01';
        } else {
            $servicePackage = \App\DataBundle::where('service_id', $data['service_id'])->first();
            if (!$servicePackage) {
                $status = '16';
            } else {
                $dataPackage = \App\DataBundle::find($data['data_bundles_id']);
                if (!$dataPackage) {            
                    $status = '19';
                } else {
                    $status = '00';
                    $data['transaction_id'] = $this->utility->generateTransactionID(2);
                    $data['status'] = 0;
                    $data['commission'] = 0;
                    $data['payment_method'] = '';
                    $data['amount'] = $dataPackage->amount;
                    $data['payment_ref'] = 'Awaiting payment';
                    $data['amount_paid'] = 0.00;
                    $dataPurchase = \App\DataTransaction::create($data);
                    // if ($data['service_id'] == '8') {
                    //     $smileInfo = $this->utility->verifySmileInfo($data['phone'], env('MODE'));
                    //     if ($smileInfo['status'] == 1) {
                            
                    //     } else {

                    //     }
                    // }
                    $resp['trans_id'] = $dataPurchase->transaction_id;
                    $resp['bundle_name'] = $dataPackage->name;
                    $resp['amountToPay'] = $dataPackage->amount;
                    $resp['phone'] = $dataPurchase->phone;
                    $resp['email'] = $dataPurchase->email;
                }
            }
        }
        return $this->utility->response($status, $field_name, $resp);
    }

    public function getBundles($networkID) {
        $bundles = \App\DataBundle::where('service_id', $networkID)->orderBy('amount', 'asc')->get();
        if(count($bundles) > 0) {
            return response()->json([
                'status'    =>  '00',
                'message'   =>  'Successful',
                'bundles'   =>  $bundles
            ]);
        }
        return response()->json([
            'status'    =>  -1,
            'message'   =>  "Unknown network ID"
        ]);
    }

    public function request(Request $request) {
        $status = '';
        $field_name = 'data';
        $resp = array();

        $data = array(
            'payment_method'    =>  $request->payment_method,
            'payment_ref'       =>  $request->payment_ref,
            'transaction_id'    =>  $request->transaction_id,
            'passcode'          =>  $request->passcode
        );

        $validator = \Validator::make($data, [
            'payment_ref'      =>  'required|string',
            'transaction_id'   =>  'required|string',
            'payment_method'   =>  'required|string',
            'passcode'         =>  'required|string'
        ]);

        if($validator->fails()) {
            $status = '05';
            return $this->utility->response($status, 'error', $validator->errors());
        }

        if ($this->utility->verifyAPIPasscode($request->transaction_id) !== $request->passcode) {
            $status = '01';
        } else {
            $dataPurchase = \App\DataTransaction::where('transaction_id', $data['transaction_id'])->first();
    
            if (count((array)$dataPurchase) <= 0) {
                $status = '07';
            } else {
                if($data['payment_method'] === "WALLET") {
                    // Wallet transaction
                    $user = \App\User::find($dataPurchase->user_id);
                    if ($user) {
                        $current_balance = $user->wallet->balance;
                        if ($current_balance >= $dataPurchase->amount) {
                            // deduct user wallet
                            $this->updateUserWallet($user, $dataPurchase, 2);
                            // update transation status
                            $this->updateTransactionInfo($dataPurchase, $data);
                            // oh yeah we got the transaction details. Simply parse the transaction details to the power vending event handler to dispense the token.
                            $this->utility->handleData($dataPurchase);
                            // as of now the event listener must have updated transaction status in the database so go get it.
                            $updatedTransaction = \App\DataTransaction::find($dataPurchase->id);
                            if($updatedTransaction->status != 2) {
                                // something must have gone wrong while trying to dispense.
                                $status = '11';
                                $resp['status'] = -1;
                                $resp['msg'] = 'Failed.';
                                $this->updateUserWallet($user, $dataPurchase, 1);
                            } else {
                                $status = '00';
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
                        if ($voucher_details->balance >= $dataPurchase->amount) {
                            $this->updateTransactionInfo($dataPurchase, $data);
                            // oh yeah we got the transaction details. Simply parse the transaction details to the power vending event handler to dispense the token.
                            $this->utility->handleData($dataPurchase);
                            // as of now the event listener must have updated transaction status in the database so go get it.
                            $updatedTransaction = \App\DataTransaction::find($dataPurchase->id)->first();
                            if($updatedTransaction->status != 2) {
                                // something must have gone wrong while trying to dispense.
                                $status = '11';
                                $resp['status'] = -1;
                                $resp['msg'] = 'Failed.';
                            } else {
                                $status = '00';
                                $voucher_details->update(['balance' => ($voucher_details->balance - $dataPurchase->amount)]);
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
                    // card transaction
                    // let us first check our money. Nna first things first.
                    if(env('PAYMENT_MODE') == 1) {
                        $verifyPayment = $this->utility->verifyPayment($data['payment_ref'], 'data', $dataPurchase->amount, $dataPurchase->id, env('MODE'));
                    } else if(env('PAYMENT_MODE') == 2) {
                        $verifyPayment = $this->utility->verifyRavePayment($data['payment_ref'], 'data', $dataPurchase->amount, $dataPurchase->id, env('MODE'));
                    } else {
                        $verifyPayment = $this->utility->verifyGladePayment($data['payment_ref'], 'data', $dataPurchase->amount, $dataPurchase->id,  env('MODE'));
                    }
            
                    if($verifyPayment == -1) {
                        $status = '10';
                        $resp['msg'] = 'We were unable to initiate the process of verifying your payment status. Please contact our customer support lines with your transaction reference for help.';
                        $resp['tNo'] = $dataPurchase->transaction_id;
                    } else if($verifyPayment == 419) {
                        $status = '08';
                        $resp['msg'] = 'Unfortunately, our servers encountered an error trying to validate your payment status. Please contact our customer support lines with your transaction reference for help.';
                        $resp['tNo'] = $dataPurchase->transaction_id;
                    } else if($verifyPayment == 404) {
                        $status = '09';
                        $resp['msg'] = 'We could not find your payment transaction reference. Your payment might have been declined. Please contact our customer support lines with your transaction reference for help.';
                        $resp['tNo'] = $dataPurchase->transaction_id;
                    } else if($verifyPayment == '503') {
                        $status = '10';
                        $resp['msg'] = 'Unable to verify transaction. Please contact our customer support lines with your transaction reference for help.';
                        $resp['tNo'] = $dataPurchase->transaction_id;
                    } else if($verifyPayment == 100) {
                        // since the users payment went through, update the payment reference.
                        $this->updateTransactionInfo($dataPurchase, $data);
                        // oh yeah we got the transaction details. Simply parse the transaction details to the power vending event handler to dispense the token.
                        $this->utility->handleData($dataPurchase);
                        // as of now the event listener must have updated transaction status in the database so go get it.
                        $updatedTransaction = \App\DataTransaction::find($dataPurchase->id)->first();
                        \Log::info('E dey here first');
                        \Log::info($updatedTransaction);
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
                        \Log::info($verifyPayment);
                        \Log::info('Other error');
                    }
                } else {
                    // invalid payment method
                    $status = '17';
                }
            }
        }
        return $this->utility->response($status, $field_name, $resp);
    }

    public function updateTransactionInfo($dataPurchase, $data) {
        $dataPurchase->update([
            'status'        =>  1,
            'payment_ref'   =>  $data['payment_ref'],
            'payment_method'=>  $data['payment_method'],
            'amount_paid'   =>  $dataPurchase->amount
        ]);
    }

    public function updateUserWallet($user, $dataPurchase, $transaction_type) {
        // 1: credit; 2: debit
        $serviceName = $this->utility->resolveServiceNameFromID($dataPurchase->service_id);
        $current_balance = $user->wallet->balance;
        if ($transaction_type == 1) {
            $new_balance = floatval($current_balance) + floatval($dataPurchase->amount);
        } else {
            $new_balance = floatval($current_balance) - floatval($dataPurchase->amount);
        }
        $user->wallet()->update(['balance' => $new_balance]);
        $description = $serviceName . ' N ' . $dataPurchase->amount . ' to ' . $dataPurchase->phone;
        $this->user->log_wallet_transaction($user, $dataPurchase->amount, $new_balance, $transaction_type, $description, 1, $dataPurchase->transaction_id);
    }

}
