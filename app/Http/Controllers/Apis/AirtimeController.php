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

    public function registerAttempt($payload) {
        $data = array(
            'phone'             =>  $payload['phone'],
            'email'             =>  $payload['email'] ?? env('DEFAULT_EMAIL_ADDRESS'),
            'amount'            =>  (int)$payload['amount'],
            'network'           =>  $payload['network'],
            'entry_points_id'   =>  $payload['reference'],
            'platform'          =>  $payload['platform'],
            'user_id'           =>  $payload['user_id'] ?? NULL
        );

        $validator = \Validator::make($data, [
            'phone'         =>  'required|digits:11',
            'email'         =>  'string|nullable',
            'amount'        =>  'required|integer|between:50,5000',
            'network'       =>  'required|string|in:"MTN", "Airtel", "Glo", "Etisalat"' //Rule::in(['MTN', 'Airtel', 'Glo', '9mobile']),
        ]);

        if($validator->fails()) {
            return [
                'status'    => false,
                'message'   => $validator->errors()->first()
            ];
        }
        
        $data['transaction_id'] = $this->utility->generateTransactionID(1);
        $data['status'] = 1;
        $data['commission'] = 0;
        $data['payment_method'] = 0;
        $data['payment_ref'] = 'Awaiting payment';
        $data['amount_paid'] = 0.00;
        $data['service_id'] = $data['network'];

        $airtimePurchase = AirtimeTransaction::create($data);
        
        if ($airtimePurchase) {
            $response['phone'] = $data['phone'];
            $response['network'] = $data['network'];
            $response['email'] = $data['email'];
            $response['amount'] = $data['amount'];
            $response['trans_id'] = $airtimePurchase->transaction_id;
            return [
                'status'    =>  true
            ];
        } 

        return [
            'status'    =>  false,
            'message'   =>  'Internal Server Error'
        ];

    }

    public function request($payload) {
        $data = array(
            'payment_method'    =>  $payload['payment_method'],
            'payment_ref'       =>  $payload['payment_reference'],
            'transaction_id'    =>  $payload['transaction_id']
        );

        $validator = \Validator::make($data, [
            'payment_ref'      =>  'required|string',
            'transaction_id'   =>  'required|string',
            'payment_method'   =>  'required|string'
        ]);

        if($validator->fails()) {
            return [
                'status'    => false,
                'message'   => $validator->errors()->first()
            ];
        }

        $airtimePurchase = \App\AirtimeTransaction::where('transaction_id', $data['transaction_id'])->first();
        if(count((array)$airtimePurchase) <= 0) {
            return [
                'status'    =>  false
            ];
        } else {
            // since the users payment went through, update the payment reference.
            $airtimePurchase->update([
                'payment_ref'       => $data['payment_ref'], 
                'payment_method'    => $data['payment_method'],
                'amount_paid'       => $airtimePurchase->amount
            ]);
            // fire event to dispense airtime
            if(floatval($airtimePurchase->amount) < 5000) {
                if(!in_array($airtimePurchase->phone, $this->blacklist)) {
                    $this->utility->handleAirtime(\App\AirtimeTransaction::find($airtimePurchase->id));
                    $updatedTransaction = \App\AirtimeTransaction::find($airtimePurchase->id);
                    if($updatedTransaction->status != 2) {
                        // something must have gone wrong while trying to dispense.
                        return [
                            'status'    =>  false
                        ];
                    } else {
                        return [
                            'status'    =>  true
                        ];
                    }
                } else {
                    return [
                        'status'    =>  false
                    ];
                }
            } else {
                return [
                    'status'    =>  false
                ];
            }
        }
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
