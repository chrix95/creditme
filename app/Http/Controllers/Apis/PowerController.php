<?php

namespace App\Http\Controllers\Apis;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\Apis\UserControllerExtension;

class PowerController extends Controller
{
    public $utility;

    public function __construct(UtilityController $utility, UserControllerExtension $user) {
        $this->utility = $utility;
        $this->user = $user;
    }

    public function getMeterInfo(Request $request) {
        $resp = array(
            'status'            =>  0,
            'msg'               =>  'Pending'
        );
        $field_name = 'data';
        $status = '00';
        $data = array(
            'disco'         =>  $request->disco,
            'meter_num'     =>  $request->meter_number,
            'amount'        =>  (int)$request->amount,
            'email'         =>  $request->email,
            'phone'         =>  $request->phone,
            'user_id'       =>  $request->user_id,
            'platform'      =>  $request->platform,
            'passcode'      =>  $request->passcode
        );

        $validator = \Validator::make($data, [
            'disco'         =>  'required|string',
            'meter_num'     =>  'required|numeric',
            'amount'        =>  'required|integer',
            'email'         =>  'string|nullable',
            'phone'         =>  'required|digits:11',
            'platform'      =>  'required|string',
            'passcode'      =>  'required|string'
        ]);

        if($validator->fails()) {
            $status = '05';
            return $this->utility->response($status, 'error', $validator->errors());
        }

        $disco = $request->disco;
        $serviceName = str_replace('_', ' ', $disco);
        $serviceInfo = $this->utility->getPowerServiceInfo($serviceName);

        if ($this->utility->verifyAPIPasscode($data['meter_num']) !== $request->passcode) {
            $status = '01';
        } else {
            if(!is_array($serviceInfo) && $serviceInfo == "404") {
                $status = '16';
                $resp['status'] = -500;
                $resp['msg'] = "Selected provider not available on our platform!";
            } else {
                if($data['amount'] < $serviceInfo->minimum_value) {
                    $status = '08';
                    $resp['status'] = -3;
                    $resp['msg'] = 'Minimum required amount is ₦'.$serviceInfo->minimum_value;
                } else if($data['amount'] > $serviceInfo->maximum_value) {
                    $status = '08';
                    $resp['status'] = 30000;
                    $resp['msg'] = 'Maximum required amount is ₦'.$serviceInfo->maximum_value;
                } else {
                    $referenceID = $this->utility->generateTransactionID(3);
                    $apiString = $this->utility->generatePowerAPIString($data['meter_num'], substr($referenceID, -12), $disco, env('MODE'));
                    $hashString = $this->utility->hashAPIString($apiString, env('MODE'));
                    $meterInfo = $this->utility->getMeterInfo($data['meter_num'], substr($referenceID, -12), $disco, $hashString, env('MODE'));
                    // remember to catch error 12 = unknown user
                    if($meterInfo['status'] == '12') {
                        $status = '01';
                        $resp['status'] = 12;
                        $resp['msg'] = 'Unknown User';
                    } else if(is_array($meterInfo['msg'])) {
                        // user meter verification successful. Log the transaction to the database.
                        $customerData = $meterInfo['msg'];
                        $data['email'] = $data['email'] ? $data['email'] : env('DEFAULT_EMAIL_ADDRESS');
                        $data['user_id'] = $data['user_id'] ? $data['user_id'] : NULL;
                        $data['transaction_id'] = $referenceID;
                        $data['customer_name'] = $customerData['name'];
                        $data['token'] = "PENDING";
                        $data['access_token'] = $meterInfo['status'];
                        $data['status'] = 0;
                        $data['amount'] = intval($data['amount']) + 100;
                        $data['amount_paid'] = 0.00;
                        $data['commission'] = $serviceInfo->commission;
                        $data['payment_method'] = 'Awaiting payment';
                        $data['payment_ref'] = 'Pending';
                        $data['units'] = '0';
                        $data['service_id'] = $serviceInfo->id;
                        \App\PowerTransaction::create($data);
                        $resp['status'] = 1;
                        $resp['msg'] = $meterInfo['msg'];
                        $resp['disco'] = $serviceInfo->name;
                        $resp['amount'] = intval($data['amount']);
                        $resp['meter_number'] = $data['meter_num'];
                        $resp['email'] = $data['email'];
                        $resp['phone'] = $data['phone'];
                        $resp['trans_id'] = $referenceID;
                        $status = '00';
                    } else {
                        $status = '15';
                        $resp['status'] = -1;
                        $resp['msg'] = 'Error while getting meter info.';
                    }
                }
            }
        }
        return $this->utility->response($status, $field_name, $resp);
    }

    public function request(Request $request) {
        $status = '';
        $field_name = 'data';
        $resp = array(
            'msg'       =>  'Pending',
        );

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
            // get the last active transaction tied to the token and this users account.
            $powerTransaction = \App\PowerTransaction::where('transaction_id', $data['transaction_id'])->where('status', 0)->first();
            if(count((array)$powerTransaction) <= 0) {
                $status = '07';
            } else {
                if($data['payment_method'] === "WALLET") {
                    // Wallet transaction
                    $user = \App\User::find($powerTransaction->user_id);
                    if ($user) {
                        $current_balance = $user->wallet->balance;
                        if ($current_balance >= $powerTransaction->amount) {
                            // deduct user wallet
                            $this->updateUserWallet($user, $powerTransaction, 2);
                            // update transation status
                            $this->updateTransactionInfo($powerTransaction, $data);
                            // oh yeah we got the transaction details. Simply parse the transaction details to the power vending event handler to dispense the token.
                            $this->utility->handlePower($powerTransaction);
                            // as of now the event listener must have updated transaction status in the database so go get it.
                            $updatedTransaction = \App\PowerTransaction::find($powerTransaction->id);
                            if($updatedTransaction->status != 2) {
                                // something must have gone wrong while trying to dispense.
                                $status = '11';
                                $resp['status'] = -1;
                                $resp['msg'] = 'Failed.';
                                $this->updateUserWallet($user, $powerTransaction, 1);
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
                        if ($voucher_details->balance >= $powerTransaction->amount) {
                            $this->updateTransactionInfo($powerTransaction, $data);
                            // oh yeah we got the transaction details. Simply parse the transaction details to the power vending event handler to dispense the token.
                            $this->utility->handlePower($powerTransaction);
                            // as of now the event listener must have updated transaction status in the database so go get it.
                            $updatedTransaction = \App\PowerTransaction::find($powerTransaction->id);
                            if($updatedTransaction->status != 2) {
                                // something must have gone wrong while trying to dispense.
                                $status = '11';
                                $resp['status'] = -1;
                                $resp['msg'] = 'Failed.';
                            } else {
                                $status = '00';
                                $voucher_details->update(['balance' => ($voucher_details->balance - $powerTransaction->amount)]);
                                $resp['token'] = $updatedTransaction->token;
                                $resp['units'] = $updatedTransaction->units;
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
                        $verifyPayment = $this->utility->verifyPayment($data['payment_ref'], 'power', $powerTransaction->amount, $powerTransaction->id,  env('MODE'));
                    } else if(env('PAYMENT_MODE') == 2) {
                        $verifyPayment = $this->utility->verifyRavePayment($data['payment_ref'], 'power', $powerTransaction->amount, $powerTransaction->id,  env('MODE'));
                    } else {
                        $verifyPayment = $this->utility->verifyGladePayment($data['payment_ref'], 'power', $powerTransaction->amount, $powerTransaction->id,  env('MODE'));
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
                    }  else if($verifyPayment == 100) {
                        // since the users payment went through, update the payment reference.
                        $this->updateTransactionInfo($powerTransaction, $data);
                        // oh yeah we got the transaction details. Simply parse the transaction details to the power vending event handler to dispense the token.
                        $this->utility->handlePower($powerTransaction);
                        // as of now the event listener must have updated transaction status in the database so go get it.
                        $updatedTransaction = \App\PowerTransaction::find($powerTransaction->id);
                        if($updatedTransaction->status != 2) {
                            // something must have gone wrong while trying to dispense.
                            $status = '11';
                            $resp['status'] = -1;
                            $resp['msg'] = 'Failed.';
                        } else {
                            $status = '00';
                            $resp['token'] = $updatedTransaction->token;
                            $resp['units'] = $updatedTransaction->units;
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

    public function updateTransactionInfo($powerTransaction, $data) {
        $powerTransaction->update([
            'status'        =>  1,
            'payment_ref'   =>  $data['payment_ref'],
            'payment_method'   =>  $data['payment_method'],
            'amount_paid'   =>  $powerTransaction->amount
        ]);
    }

    public function updateUserWallet($user, $powerTransaction, $transaction_type) {
        // 1: credit; 2: debit
        $serviceName = \App\Service::find($powerTransaction->service_id);
        $current_balance = $user->wallet->balance;
        if ($transaction_type == 1) {
            $new_balance = floatval($current_balance) + floatval($powerTransaction->amount);
        } else {
            $new_balance = floatval($current_balance) - floatval($powerTransaction->amount);
        }
        $user->wallet()->update(['balance' => $new_balance]);
        $description = $serviceName->name . ' N ' . $powerTransaction->amount . ' to ' . $powerTransaction->meter_num;
        $this->user->log_wallet_transaction($user, $powerTransaction->amount, $new_balance, $transaction_type, $description, 1, $powerTransaction->transaction_id);
    }

}
