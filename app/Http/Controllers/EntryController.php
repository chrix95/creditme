<?php

namespace App\Http\Controllers;

use \App\EntryPoint;
use Illuminate\Http\Request;
use App\Http\Controllers\UtilityController;
use \App\Http\Controllers\Apis\AirtimeController;
use \App\Http\Controllers\Apis\DataController;

class EntryController extends Controller
{
    public $utility;
    public $airtime;
    public $data;
    
    public function __construct(UtilityController $utility, AirtimeController $airtime, DataController $data) {
        $this->utility = $utility;
        $this->airtime = $airtime;
        $this->data = $data;
    }

    public function airtimeEntry (Request $request) {
        $data = array(
            'phone'    =>  $request->phone,
            'payload'  =>  $request->payload, // [{ network, phone, amount }, ..., {}]
            'platform' =>  $request->platform,
            'passcode' =>  $request->passcode
        );

        $validator = \Validator::make($data, [
            'phone'     =>  'required|digits:11',
            'payload'   =>  'required',
            'platform'  =>  'required|string',
            'passcode'  =>  'required|string'
        ]);

        if($validator->fails()) {
            return response()->json([
                'status'    =>  false,
                'message'   =>  $validator->errors()->first()
            ]);
        }

        if (count($data['payload']) === 0) {
            return response()->json([
                'status'    =>  false,
                'message'   =>  "Phone numbers not provided"
            ]);
        }

        if ($this->utility->verifyAPIPasscode($data['phone']) !== $data['passcode']) {
            return response()->json([
                'status'    =>  false,
                'message'   =>  'Unknown request sent'
            ]);
        }

        // Generate unique reference using time and rand
        $reference = time() . rand(10*45, 100*98);
        $transaction_sum = array_sum(array_column($data['payload'], 'amount'));
        $convenience_fee = $transaction_sum >= 2500 ? ($transaction_sum * (1.5/100)) + 100 : ($transaction_sum * (1.5/100));

        $entryPoint = new EntryPoint();
        $entryPoint->phone_number = $data['phone'];
        $entryPoint->total_amount = $transaction_sum;
        $entryPoint->convenience_fee = $convenience_fee;
        $entryPoint->reference = $reference;
        $entryPoint->service = 'airtime';
        $entryPoint->platform = $data['platform'];
        $entryPoint->transaction_count = count($data['payload']);
        $entryPoint->status = 'Initiated';

        foreach($data['payload'] as $airtime) {
            $payload = array(
                'phone'     =>  $airtime['phone'] ?? NULL,
                'amount'    =>  (int)$airtime['amount'] ?? NULL,
                'network'   =>  $airtime['network'] ?? NULL,
                'platform'  =>  $data['platform'],
                'reference' =>  $reference,
            );
            $response = $this->airtime->registerAttempt($payload);
            if ($response['status'] == false) {
                return response()->json([
                    'status'    => false,
                    'message'   =>  $response['message']
                ]);
            }
        }

        $entryPoint->save();

        return response()->json([
            'status'    =>  true,
            'message'   =>  "Successful",
            'transaction_ref'   =>  $entryPoint->reference,
            'transaction_count'   =>  $entryPoint->transaction_count,
            'convenience_fee'   =>  $convenience_fee,
            'total_amount'   =>  $transaction_sum
        ]);
    }

    public function airtimeVend (Request $request) {
        $data = array(
            'transaction_reference' =>  $request->transaction_reference,
            'payment_reference'     =>  $request->payment_reference,
            'payment_method'        =>  $request->payment_method,
            'passcode'              =>  $request->passcode
        );

        $validator = \Validator::make($data, [
            'transaction_reference' =>  'required',
            'payment_reference'     =>  'required',
            'payment_method'        =>  'required|string',
            'passcode'              =>  'required|string'
        ]);

        if($validator->fails()) {
            return response()->json([
                'status'    =>  false,
                'message'   =>  $validator->errors()->first()
            ]);
        }

        if ($this->utility->verifyAPIPasscode($data['transaction_reference']) !== $data['passcode']) {
            return response()->json([
                'status'    =>  false,
                'message'   =>  'Unknown request sent'
            ]);
        }

        $transaction = EntryPoint::where('reference', $data['transaction_reference'])->first();

        if ($transaction) {
            $transaction->payment_reference = $data['payment_reference'];
            $transaction->payment_method = $data['payment_method'];
            if ($data['payment_method'] == 'paystack') {
                $amountToVerify = $transaction->total_amount + $transaction->convenience_fee;
                $verified = $this->utility->verifyPaystackPayment($data['payment_reference'], $amountToVerify);
                if ($verified == 0 || $verified == 503) {
                    $response = 'Unable to verify transaction! Kindly contact support.';
                    $transaction->status = $response;
                    $transaction->save();
                    return response()->json([
                        'status'    =>  false,
                        'message'   =>  $response
                    ]);
                } else if ($verified == 404) {
                    $response = 'Payment transaction reference not found.';
                    $transaction->status = $response;
                    $transaction->save();
                    return response()->json([
                        'status'    =>  false,
                        'message'   =>  $response
                    ]);
                } else if ($verified == 419) {
                    $response = 'Transaction Amount paid mismatch. Kindly contact support';
                    $transaction->status = $response;
                    $transaction->save();
                    return response()->json([
                        'status'    =>  false,
                        'message'   =>   $response
                    ]);
                } else {
                    $response = 'Payment verified';
                    $transaction->status = $response;
                    $transaction->save();
                    return response()->json([
                        'status'    =>  true,
                        'message'   =>  'Payment verified successfully. Transaction will be completed shortly.',
                        'transaction_reference' => $transaction->reference,
                        'total_amount' => $transaction->total_amount,
                        'convenience_fee' => $transaction->convenience_fee,
                        'sub_transaction' => $transaction->airtimeTransaction
                    ]);
                }
            } else {
                return response()->json([
                    'status'    =>  false,
                    'message'   =>  'Invalid payment option selected'
                ]);
            }
        } else {
            return response()->json([
                'status'    =>  false,
                'message'   =>  'Invalid transaction reference'
            ]);
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

    public function dataEntry (Request $request) {
        $data = array(
            'phone'    =>  $request->phone,
            'payload'  =>  $request->payload, // [{ network, phone, amount }, ..., {}]
            'platform' =>  $request->platform,
            'passcode' =>  $request->passcode
        );

        $validator = \Validator::make($data, [
            'phone'     =>  'required|digits:11',
            'payload'   =>  'required',
            'platform'  =>  'required|string',
            'passcode'  =>  'required|string'
        ]);

        if($validator->fails()) {
            return response()->json([
                'status'    =>  false,
                'message'   =>  $validator->errors()->first()
            ]);
        }

        if (count($data['payload']) === 0) {
            return response()->json([
                'status'    =>  false,
                'message'   =>  "Data numbers are not provided"
            ]);
        }

        if ($this->utility->verifyAPIPasscode($data['phone']) !== $data['passcode']) {
            return response()->json([
                'status'    =>  false,
                'message'   =>  'Unknown request sent'
            ]);
        }

        // Generate unique reference using time and rand
        $reference = time() . rand(10*45, 100*98);
        // Save each transaction and get the sum
        $transaction_sum = 0;
        foreach($data['payload'] as $item) {
            $payload = array(
                'phone'             =>  $item['phone'] ?? NULL,
                'service_id'        =>  $item['service_id'] ?? NULL,
                'data_bundles_id'   =>  $item['data_bundles_id'] ?? NULL,
                'platform'          =>  $data['platform'],
                'reference'         =>  $reference
            );
            $response = $this->data->registerAttempt($payload);
            if ($response['status'] == false) {
                return response()->json([
                    'status'    => false,
                    'message'   =>  $response['message']
                ]);
            }
            $transaction_sum = (float)$transaction_sum + (float)$response['amount'];
        }
        $convenience_fee = $transaction_sum >= 2500 ? ($transaction_sum * (1.5/100)) + 100 : ($transaction_sum * (1.5/100));

        $entryPoint = new EntryPoint();
        $entryPoint->phone_number = $data['phone'];
        $entryPoint->total_amount = $transaction_sum;
        $entryPoint->convenience_fee = $convenience_fee;
        $entryPoint->reference = $reference;
        $entryPoint->platform = $data['platform'];
        $entryPoint->service = 'data';
        $entryPoint->transaction_count = count($data['payload']);
        $entryPoint->status = 'Initiated';
        $entryPoint->save();

        return response()->json([
            'status'    =>  true,
            'message'   =>  "Successful",
            'transaction_ref'   =>  $entryPoint->reference,
            'transaction_count'   =>  $entryPoint->transaction_count,
            'convenience_fee'   =>  number_format($convenience_fee, 2),
            'total_amount'   =>  $transaction_sum
        ]);
    }

    public function dataVend (Request $request) {
        $data = array(
            'transaction_reference' =>  $request->transaction_reference,
            'payment_reference'     =>  $request->payment_reference,
            'payment_method'        =>  $request->payment_method,
            'passcode'              =>  $request->passcode
        );

        $validator = \Validator::make($data, [
            'transaction_reference' =>  'required',
            'payment_reference'     =>  'required',
            'payment_method'        =>  'required|string',
            'passcode'              =>  'required|string'
        ]);

        if($validator->fails()) {
            return response()->json([
                'status'    =>  false,
                'message'   =>  $validator->errors()->first()
            ]);
        }

        if ($this->utility->verifyAPIPasscode($data['transaction_reference']) !== $data['passcode']) {
            return response()->json([
                'status'    =>  false,
                'message'   =>  'Unknown request sent'
            ]);
        }

        $transaction = EntryPoint::where('reference', $data['transaction_reference'])->where('service', 'data')->first();

        if ($transaction) {
            $transaction->payment_reference = $data['payment_reference'];
            $transaction->payment_method = $data['payment_method'];
            if ($data['payment_method'] == 'paystack') {
                $amountToVerify = $transaction->total_amount + $transaction->convenience_fee;
                $verified = $this->utility->verifyPaystackPayment($data['payment_reference'], $amountToVerify);
                if ($verified == 0 || $verified == 503) {
                    $response = 'Unable to verify transaction! Kindly contact support.';
                    $transaction->status = $response;
                    $transaction->save();
                    return response()->json([
                        'status'    =>  false,
                        'message'   =>  $response
                    ]);
                } else if ($verified == 404) {
                    $response = 'Payment transaction reference not found.';
                    $transaction->status = $response;
                    $transaction->save();
                    return response()->json([
                        'status'    =>  false,
                        'message'   =>  $response
                    ]);
                } else if ($verified == 419) {
                    $response = 'Transaction Amount paid mismatch. Kindly contact support';
                    $transaction->status = $response;
                    $transaction->save();
                    return response()->json([
                        'status'    =>  false,
                        'message'   =>   $response
                    ]);
                } else {
                    $response = 'Payment verified';
                    $transaction->status = $response;
                    $transaction->save();
                    return response()->json([
                        'status'    =>  true,
                        'message'   =>  'Payment verified successfully. Transaction will be completed shortly.',
                        'transaction_reference' => $transaction->reference,
                        'total_amount' => $transaction->total_amount,
                        'convenience_fee' => $transaction->convenience_fee,
                        'sub_transaction' => $transaction->airtimeTransaction
                    ]);
                }
            } else {
                return response()->json([
                    'status'    =>  false,
                    'message'   =>  'Invalid payment option selected'
                ]);
            }
        } else {
            return response()->json([
                'status'    =>  false,
                'message'   =>  'Invalid transaction reference'
            ]);
        }
    }

}
