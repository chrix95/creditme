<?php

namespace App\Http\Controllers\Apis;

use App\AirtimeTransaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\Apis\UserControllerExtension;

class AirtimeController extends Controller
{
    public $utility;

    public $blacklist = array();

    public function __construct(UtilityController $utility, UserControllerExtension $user) {
        $this->utility = $utility;
        $this->user = $user;
    }

    public function registerAttempt(Request $request) {
        $response = [];
        $status = '';
        $field_name = '';
        $data = array(
            'phone'         =>  $request->phone,
            'email'         =>  $request->email,
            'amount'        =>  (int)$request->amount,
            'network'       =>  $request->network,
            'platform'      =>  $request->platform,
            'user_id'       =>  $request->user_id,
            'passcode'      =>  $request->passcode
        );

        $validator = \Validator::make($data, [
            'phone'         =>  'required|digits:11',
            'email'         =>  'string|nullable',
            'amount'        =>  'required|integer|between:50,5000',
            'network'       =>  'required|string|in:"MTN", "Airtel", "Glo", "Etisalat"', //Rule::in(['MTN', 'Airtel', 'Glo', '9mobile']),
            'platform'      =>  'required|string',
            'passcode'      =>  'required|string'
        ]);

        if($validator->fails()) {
            $status = '05';
            return $this->utility->response($status, 'error', $validator->errors());
        }
        
        $data['email'] = $data['email'] ? $data['email'] : env('DEFAULT_EMAIL_ADDRESS');
        $data['user_id'] = $data['user_id'] ? $data['user_id'] : NULL;
        $data['transaction_id'] = $this->utility->generateTransactionID(1);
        $data['status'] = 1;
        $data['commission'] = 0;
        $data['payment_method'] = 0;
        $data['payment_ref'] = 'Awaiting payment';
        $data['amount_paid'] = 0.00;
        $data['service_id'] = $data['network'];

        if ($this->utility->verifyAPIPasscode($request->phone) !== $request->passcode) {
            $status = '01';
        } else {
            $airtimePurchase = AirtimeTransaction::create($data);
            $response['phone'] = $data['phone'];
            $response['network'] = $data['network'];
            $response['email'] = $data['email'];
            $response['amount'] = $data['amount'];
            $response['trans_id'] = $airtimePurchase->transaction_id;
            $status = '00';
            $field_name = 'data';
        }
        return $this->utility->response($status, $field_name, $response);
    }

    public function request(Request $request) {
        $response = [];
        $status = '';
        $field_name = 'item';
        $data = array(
            'payment_method'    =>  $request->payment_method,
            'payment_ref'       =>  $request->payment_ref,
            'transaction_id'    =>  $request->transaction_id,
            'passcode'          =>  $request->passcode
        );

        $validator = \Validator::make($data, [
            'payment_ref'      =>  'required|string',
            'transaction_id'   =>  'required|string',
            'payment_method'   =>  'string',
            'passcode'         =>  'string'
        ]);

        if($validator->fails()) {
            $status = '05';
            return $this->utility->response($status, 'error', $validator->errors());
        }

        if ($this->utility->verifyAPIPasscode($request->transaction_id) !== $request->passcode) {
            $status = '01';
            return $this->utility->response($status, $field_name, $response);
        } else {
            $airtimePurchase = \App\AirtimeTransaction::where('transaction_id', $data['transaction_id'])->first();
            if(count((array)$airtimePurchase) <= 0) {
                $status = '07'; 
            } else {
                if($data['payment_method'] === "WALLET") {
                    // Wallet transaction
                    $user = \App\User::find($airtimePurchase->user_id);
                    if ($user) {
                        $current_balance = $user->wallet->balance;
                        if ($current_balance >= $airtimePurchase->amount) {
                            // deduct user wallet
                            $this->updateUserWallet($user, $airtimePurchase, 2);
                            // update transation status
                            $this->updateTransactionInfo($airtimePurchase, $data);
                            // oh yeah we got the transaction details. Simply parse the transaction details to the power vending event handler to dispense the token.
                            $this->utility->handleAirtime($airtimePurchase);
                            // as of now the event listener must have updated transaction status in the database so go get it.
                            $updatedTransaction = \App\AirtimeTransaction::find($airtimePurchase->id);
                            if($updatedTransaction->status != 2) {
                                // something must have gone wrong while trying to dispense.
                                $status = '11';
                                $response['status'] = -1;
                                $response['msg'] = 'Failed.';
                                $this->updateUserWallet($user, $airtimePurchase, 1);
                            } else {
                                $status = '00';
                                $response['message'] = 'You should receive your airtime shortly with a notification to your e-mail and phone number.';
                                $response['transaction_id'] = $airtimePurchase->transaction_id;
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
                        if ($voucher_details->balance >= $airtimePurchase->amount) {
                            if(floatval($airtimePurchase->amount) < 5000) {
                                if(!in_array($airtimePurchase->phone, $this->blacklist)) {
                                    // since the users payment went through, update the payment reference.
                                    $this->updateTransactionInfo($airtimePurchase, $data);
                                    $this->utility->handleAirtime(\App\AirtimeTransaction::find($airtimePurchase->id));
                                    $updatedTransaction = \App\AirtimeTransaction::find($airtimePurchase->id);
                                    if($updatedTransaction->status != 2) {
                                        // something must have gone wrong while trying to dispense.
                                        $status = '11';
                                        $response['status'] = -1;
                                        $response['msg'] = 'Failed.';
                                        $this->updateUserWallet($user, $airtimePurchase, 1);
                                    } else {
                                        $voucher_details->update(['balance' => ($voucher_details->balance - $airtimePurchase->amount)]);
                                        $status = '00';
                                        $response['message'] = 'You should receive your airtime shortly with a notification to your e-mail and phone number.';
                                        $response['transaction_id'] = $airtimePurchase->transaction_id;
                                    }
                                } else {
                                    $status = '12';
                                    $response['transaction_id'] = $airtimePurchase->transaction_id;
                                    \Log::info('fraudluent number detected.');
                                }
                            } else {
                                $status = '11';
                                $response['transaction_id'] = $airtimePurchase->transaction_id;
                            }
                        } else {
                            $status = '14';
                            $response['message'] = "Voucher balance is " . $voucher_details->balance . ". Kindly select a different payment method.";
                        }
                    } else {
                        $status = '13';
                        $response['message'] = "Invalid voucher code/voucher has expired. Kindly select a different payment method.";
                    }
                } else if ($data['payment_method'] === "CARD") {
                    // Card transaction
                    // since the users payment went through, update the payment reference.
                    $this->updateTransactionInfo($airtimePurchase, $data);
                    // after committing the transaction to the database, now lets try to verify whether the payment was actually successful before vending.
                    if(env('PAYMENT_MODE') == 1) {
                        $verifyPayment = $this->utility->verifyPayment($data['payment_ref'], 'airtime', $airtimePurchase->amount, $airtimePurchase->id, env('MODE'));
                    } else if(env('PAYMENT_MODE') == 2) {
                        $verifyPayment = $this->utility->verifyRavePayment($data['payment_ref'], 'airtime', $airtimePurchase->id, env('MODE'));
                    } else {
                        $verifyPayment = $this->utility->verifyGladePayment($data['payment_ref'], 'airtime', $airtimePurchase->id, env('MODE'));
                    }
                    // check the status returned from payment and proceed
                    if($verifyPayment == -1) {
                        \Log::info($verifyPayment);
                        \Log::info('Verification issue');
                        $status = '10';
                    } else if($verifyPayment == 419) {
                        $status = '08';
                    } else if($verifyPayment == 404) {
                        $status = '09';
                    } else if($verifyPayment == '503') {
                        \Log::info($verifyPayment);
                        $status = '10';
                    } else if($verifyPayment == 100) {
                        // fire event to dispense airtime
                        if(floatval($airtimePurchase->amount) < 5000) {
                            if(!in_array($airtimePurchase->phone, $this->blacklist)) {
                                $this->utility->handleAirtime(\App\AirtimeTransaction::find($airtimePurchase->id));
                                $updatedTransaction = \App\AirtimeTransaction::find($airtimePurchase->id);
                                if($updatedTransaction->status != 2) {
                                    // something must have gone wrong while trying to dispense.
                                    $status = '11';
                                    $response['status'] = -1;
                                    $response['msg'] = 'Failed.';
                                    $this->updateUserWallet($user, $airtimePurchase, 1);
                                } else {
                                    $status = '00';
                                    $response['message'] = 'You should receive your airtime shortly with a notification to your e-mail and phone number.';
                                    $response['transaction_id'] = $airtimePurchase->transaction_id;
                                }
                            } else {
                                $status = '12';
                                $response['transaction_id'] = $airtimePurchase->transaction_id;
                                \Log::info('fraudluent number detected.');
                            }
                        } else {
                            $status = '11';
                            $response['transaction_id'] = $airtimePurchase->transaction_id;
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
            return $this->utility->response($status, $field_name, $response);
        }
    }

    public function updateTransactionInfo($airtimePurchase, $data) {
        $airtimePurchase->update([
            'payment_ref'       => $data['payment_ref'], 
            'payment_method'    => $data['payment_method'],
            'amount_paid'       => $airtimePurchase->amount
        ]);
    }

    public function updateUserWallet($user, $airtimePurchase, $transaction_type) {
        // 1: credit; 2: debit
        $current_balance = $user->wallet->balance;
        if ($transaction_type == 1) {
            $new_balance = floatval($current_balance) + floatval($airtimePurchase->amount);
        } else {
            $new_balance = floatval($current_balance) - floatval($airtimePurchase->amount);
        }
        $user->wallet()->update(['balance' => $new_balance]);
        $description = $airtimePurchase->service_id . ' N ' . $airtimePurchase->amount . ' to ' . $airtimePurchase->phone;
        $this->user->log_wallet_transaction($user, $airtimePurchase->amount, $new_balance, $transaction_type, $description, 1, $airtimePurchase->transaction_id);
    }

}
