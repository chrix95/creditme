<?php

namespace App\Http\Controllers\Cronjob;

use App\EntryPoint;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UtilityController;
use \App\Http\Controllers\Apis\AirtimeController;

class ServicesVendController extends Controller
{
    public $utility;
    public $airtime;
    public function __construct(UtilityController $utility, AirtimeController $airtime) {
        $this->utility = $utility;
        $this->airtime = $airtime;
    }

    public function processAirtimeTransactions (Request $request) {
        $transactions = EntryPoint::where('status', 'Payment verified')->get();
        if (count($transactions) == 0) {
            return "No record to vend";
        }
        foreach ($transactions as $transaction) {
            $transaction->status = 'Vending in progress...';
            $transaction->save();
            foreach ($transaction->airtimeTransaction as $key => $value) {
                $payload = array(
                    'payment_method'    =>  $transaction->payment_method,
                    'payment_reference' =>  $transaction->payment_reference,
                    'transaction_id'    =>  $value->transaction_id
                );
                $response = $this->airtime->request($payload);
                if ($response['status'] == false) {
                    \Log::info("Something went wrong while vending");
                    \Log::info("Failed transactions");
                    \Log::info($value->transaction_id);
                }
            }
            $message = 'Vending Completed';
            $transaction->status = $message;
            $transaction->save();
        }
        return response()->json([
            'status'    =>  true,
            'message'   =>  $message
        ]);
    }

}