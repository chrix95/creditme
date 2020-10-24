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

    public function registerAttempt($payload) {
        $data = array(
            'phone'             =>  $payload['phone'],
            'email'             =>  $payload['email'] ?? env('DEFAULT_EMAIL_ADDRESS'),
            'service_id'        =>  $payload['service_id'],
            'data_bundles_id'   =>  $payload['data_bundles_id'],
            'user_id'           =>  $payload['user_id'] ?? NULL,
            'platform'          =>  $payload['platform'],
            'entry_points_id'   =>  $payload['reference']
        );

        $validator = \Validator::make($data, [
            'phone'             =>  'required|digits:11',
            'email'             =>  'string|nullable',
            'service_id'        =>  'required|numeric',
            'data_bundles_id'   =>  'required|numeric'
        ]);

        if($validator->fails()) {
            return [
                'status'    => false,
                'message'   => $validator->errors()->first()
            ];
        }

        $servicePackage = \App\DataBundle::where('service_id', $data['service_id'])->where('id', $data['data_bundles_id'])->first();
        if (!$servicePackage) {
            return [
                'status'    =>  false,
                'message'   =>  'Invalid service ID/bundle ID match provided'
            ];
        } else {
            $data['transaction_id'] = $this->utility->generateTransactionID(2);
            $data['status'] = 1;
            $data['commission'] = 0;
            $data['payment_method'] = 'Awaiting payment';
            $data['amount'] = $servicePackage->amount;
            $data['payment_ref'] = 'Awaiting payment';
            $data['amount_paid'] = 0.00;
            $dataPurchase = \App\DataTransaction::create($data);
            return [
                'status'   =>  true,
                'amount'   =>  $servicePackage->amount
            ];
        }
    }

    public function getBundles($networkID) {
        $bundles = \App\DataBundle::where('service_id', $networkID)->orderBy('amount', 'asc')->get();
        if(count($bundles) > 0) {
            return response()->json([
                'status'    =>  true,
                'message'   =>  'Successful',
                'bundles'   =>  $bundles
            ]);
        }
        return response()->json([
            'status'    =>  false,
            'message'   =>  "Unknown network ID"
        ]);
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
            \Log::info("validation");
            \Log::info($validator->errors()->first());
            return [
                'status'    => false,
                'message'   => $validator->errors()->first()
            ];
        }

        $dataPurchase = \App\DataTransaction::where('transaction_id', $data['transaction_id'])->first();
    
        if (count((array)$dataPurchase) <= 0) {
            \Log::info("no data purchase");
            return [
                'status'    =>  false
            ];
        } else {
            // since the users payment went through, update the payment reference.
            $dataPurchase->update([
                'payment_ref'   =>  $data['payment_ref'],
                'payment_method'=>  $data['payment_method'],
                'amount_paid'   =>  $dataPurchase->amount
            ]);
            // oh yeah we got the transaction details. Simply parse the transaction details to the power vending event handler to dispense the token.
            $this->utility->handleData($dataPurchase);
            // as of now the event listener must have updated transaction status in the database so go get it.
            $updatedTransaction = \App\DataTransaction::find($dataPurchase->id)->first();
            if($updatedTransaction->status != 2) {
                // something must have gone wrong while trying to dispense.
                \Log::info("failed to dispense");
                return [
                    'status'    =>  false
                ];
            } else {
                \Log::info("dispensed");
                return [
                    'status'    =>  true
                ];
            }
        }
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
