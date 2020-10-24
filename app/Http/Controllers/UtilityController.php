<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UtilityController extends Controller
{

    public function errorCode($code) {
        $errorCode = array(
            "00"    =>  "Successful",
            "01"    =>  "Unknown User",
            "02"    =>  "Created",
            "03"    =>  "Invalid Credentials",
            "04"    =>  "User not allowed. Contact Administrator for support",
            "05"    =>  "Validation Error",
            "06"    =>  "No Result found",
            "07"    =>  "Transaction ID not found",
            "08"    =>  "Invalid amount",
            "09"    =>  "Transaction reference not found",
            "10"    =>  "Service Unavailable",
            "11"    =>  "Kindly contact our customer service lines for more information.",
            "12"    =>  "Fradulent transaction detected.",
            "13"    =>  "Invalid voucher code/expired voucher.",
            "14"    =>  "Low voucher balance.",
            "15"    =>  "Error while getting meter info.",
            "16"    =>  "Selected provider not available on our platform!",
            "17"    =>  "Invalid payment method",
            "18"    =>  "Error retrieving smartcard info.",
            "19"    =>  "Selected package does not exist.",
            "20"    =>  "Low wallet balance",
            "21"    =>  "Invalid User Credentials"
        );
        return $errorCode[$code];
    }

    public function headerCode($code) {
        $headerCode = array(
            "00"    =>  200,
            "01"    =>  401,
            "02"    =>  201,
            "03"    =>  401,
            "04"    =>  403,
            "05"    =>  406,
            "06"    =>  200,
            "07"    =>  404,
            "08"    =>  400,
            "09"    =>  404,
            "10"    =>  503,
            "11"    =>  200,
            "12"    =>  403,
            "13"    =>  400,
            "14"    =>  400,
            "15"    =>  500,
            "16"    =>  503,
            "17"    =>  404,
            "18"    =>  500,
            "19"    =>  422,
            "20"    =>  400,
            "21"    =>  401
        );
        return $headerCode[$code];
    }

    public function response($status, $dataName, $data) {
        $errorMessage = $this->errorCode($status); // get error code message
        $header_code = $this->headerCode($status); // get header code message
        $resp = array(
            'status'    =>  $status,
            'message'   =>  $errorMessage,
        );
        if(count($data) > 0) {
            $resp[$dataName] = $data;
        }
        return response()->json($resp, $header_code);
    }

    public function sendSMS($message, $phone) {
        $curl = curl_init();
        $base_url= "http://login.betasms.com.ng/api/?";
        $username="care@sundiatapost.com";
        $password= "Sundiata123";

        $sender = "Sundiata";

        $message= str_replace("\n", " ", $message);
        $message= str_replace(" ", "%20", $message);

        $api_call=$base_url."username=".$username."&password=".$password."&sender=".$sender."&mobiles=".$phone."&message=".$message;

        curl_setopt_array($curl, array(
            CURLOPT_URL => $api_call,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "postman-token: 906571e4-3767-097f-fad8-3524f771e188"
            ),
        ));

        $response = curl_exec($curl);
        Log::info("Response from beta sms");
        Log::info($response);
        $err = curl_error($curl);

        curl_close($curl);
    }

    public function generateTransactionID($service) {
        $prefix = '';
        $transactionID = 0;
        switch($service) {
            case 1:
                //airtime
                $id = random_int(1000, 999999999999);
                $length = strlen((string)$id);
                if($length < 12 || $length > 12) {
                    $id = $this->generateTransactionID(1);
                }
                $transactionID = 'AIR-' . $id;
                break;
            case 2:
                // data
                $id = random_int(1000, 999999999999);
                $length = strlen((string)$id);
                if($length < 12 || $length > 12) {
                    $id = $this->generateTransactionID(2);
                }
                $transactionID = 'DAT-' . $id;
                break;
            case 3:
                // Power
                $id = random_int(1000, 999999999999);
                $length = strlen((string)$id);
                if($length < 12 || $length > 12) {
                    $id = $this->generateTransactionID(3);
                }
                $transactionID = 'POW-' . $id;
                break;
            case 4:
                // TV
                $id = random_int(1000, 999999999999);
                $length = strlen((string)$id);
                if($length < 12 || $length > 12) {
                    $id = $this->generateTransactionID(4);
                }
                $transactionID = 'TV-' . $id;
                break;
        }

        return $transactionID;
    }

    public function verifyAPIPasscode($apiString) {
        $key = env('VERIFY_HASH_KEY');
        return hash_hmac("sha1", $apiString, $key);
    }

    public function verifyPayment($paymentReference, $transactionType, $amount, $transactionID, $mode = 1) {
        Log::info('lets try to verify payment on paystack');
        $verified = 0;
        $result = array();
        $key = env('PAYSTACK_TEST_PRIVATE_KEY');
        if($mode == 2) {
            $key = env('PAYSTACK_LIVE_PRIVATE_KEY');
        }
        $url = 'https://api.paystack.co/transaction/verify/' . $paymentReference;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $key]
        );
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $request = curl_exec($ch);
        if(curl_errno($ch)) {
            $verified = curl_errno($ch);
            Log::info('cURL error occured while trying to verify payment.');
            Log::error(curl_error($ch));
            // change status for the transaction to failed so in case the payment actually went through, it can be retried.
            switch($transactionType) {
                case 'airtime':
                    $transaction = \App\AirtimeTransaction::find($transactionID);
                    $transaction->update(['status' => 3]);
                    break;
                case 'data':
                    $transaction = \App\DataTransaction::find($transactionID);
                    $transaction->update(['status' => 3]);
                    break;
                case 'power':
                    $transaction = \App\PowerTransaction::find($transactionID);
                    $transaction->update(['status' => 3]);
                    break;
                case 'tv':
                    $transaction = \App\TVController::find($transactionID);
                    $transaction->update(['status' => 3]);
                    break;
            }
        } else {
            if ($request) {
                $result = json_decode($request, true);
                Log::info('result from verifying payment');
                Log::info($result);
                if($result["status"] == true) {
                    if($result["data"] && $result["data"]["status"] == "success") {
                        // at this point, payment has been verified.
                        // launch an event on a queue to send email of receipt to user.
                        Log::info('Payment successfully verified.');
                        $real_amount_paid = $result['data']['amount'] / 100;
                        if($amount == $real_amount_paid) {
                            $verified = 100;
                        } else {
                            // amount paid isn't equal to the expected amount
                            $verified = 419;
                        }
                    } else {
                        // transaction not found
                        $verified = 404;
                    }
                }  else {
                    // $resp['msg'] = 'Transaction not found!';
                    $verified = 404;
                }
            } else {
                // $resp['msg'] = 'Unable to verify transaction!';
                $verified = 503;
            }
        }
        curl_close($ch);

        return $verified;
    }

    public function verifyPaystackPayment($paymentReference, $amount, $mode = 1) {
        $verified = 0;
        $result = array();
        $key = env('PAYSTACK_TEST_PRIVATE_KEY');
        if($mode == 2) {
            $key = env('PAYSTACK_LIVE_PRIVATE_KEY');
        }
        $url = 'https://api.paystack.co/transaction/verify/' . $paymentReference;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $key]
        );
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $request = curl_exec($ch);
        if(curl_errno($ch)) {
            $verified = curl_errno($ch);
            Log::info('cURL error occured while trying to verify payment.');
            Log::error(curl_error($ch));
        } else {
            if ($request) {
                $result = json_decode($request, true);
                Log::info('result from verifying payment');
                Log::info($result);
                if($result["status"] == true) {
                    if($result["data"] && $result["data"]["status"] == "success") {
                        // at this point, payment has been verified.
                        // launch an event on a queue to send email of receipt to user.
                        Log::info('Payment successfully verified.');
                        $real_amount_paid = $result['data']['amount'] / 100;
                        if($amount == $real_amount_paid) {
                            $verified = 100;
                        } else {
                            // amount paid isn't equal to the expected amount
                            $verified = 419;
                        }
                    } else {
                        // transaction not found
                        $verified = 404;
                    }
                }  else {
                    // $resp['msg'] = 'Transaction not found!';
                    $verified = 404;
                }
            } else {
                // $resp['msg'] = 'Unable to verify transaction!';
                $verified = 503;
            }
        }
        curl_close($ch);

        return $verified;
    }

    // all irecharge generations
    public function purifyJSON($json) {
        if(substr($json, 0, 3) == pack("CCC", 0xEF, 0xBB, 0xBF)) {
            $json = substr($json, 3);
        }
        return $json;
    }

    public function remove_utf8_bom($json) {
        $bom = pack('H*','EFBBBF');
        $json = preg_replace("/^$bom/", '', $json);
        return $json;
    }

    public function resolveServiceNameFromID($serviceID, $needsAPI = 0) {
        $service = \App\Service::find($serviceID);
        if(!$service) {
            return "s404";
        }
        if(!$needsAPI) {
            return $service->name == "9mobile" ? "Etisalat" : $service->name;
        }
        return $service->api_id;
    }

    public function checkAPITransactionStatus($apiID, $receiver) {
        $resp = array(
            'status'    => 0,
            'msg'       => 'Pending'
        );
        $timeStamp = date('Y-m-d');
        $statuses = \App\ApiRequest::where('api_id', $apiID)->where('receiver', $receiver)->where('request_timestamp', 'LIKE', '%' . $timeStamp . '%')->get();

        $objects = [];
        if(count($statuses) > 0) {
            $resp['status'] = 1;
            foreach($statuses as $status) {
                if($status->status === 0) {
                    $objects[] = $status->id;
                }
            }
        } else {
            $objects = 0;
        }
        $resp['msg'] = $objects;
    }

    public function hashAPIString($apiString, $mode = 1) {
        Log::info('Hashing api string for request.');
        $key = env('TEST_PRIVATE_KEY');
        if($mode == 2) {
            $key = env('LIVE_PRIVATE_KEY');
        }
        Log::info(hash_hmac("sha1", $apiString, $key));

        return hash_hmac("sha1", $apiString, $key);
    }

    // airtime manipulations
    public function generateAirtimeAPIString($transactionID, $receiver, $serviceName, $amount, $mode = 1) {
        Log::info('generating api string for airtime purchase.');
        $key = env('TEST_PUBLIC_KEY');
        if($mode == 2) {
            $key = env('LIVE_PUBLIC_KEY');
        }
        Log::info(env('VENDOR_CODE')."|".$transactionID."|".$receiver."|".$serviceName."|".$amount."|".$key);

        return env('VENDOR_CODE')."|".$transactionID."|".$receiver."|".$serviceName."|".$amount."|".$key;
    }

    public function handleAirtime($airtimeTransaction) {
        // first lets update the transaction status to processing.
        \App\AirtimeTransaction::where('id', $airtimeTransaction->id)->update([
            'status'    =>  1
        ]);
        $serviceName = $airtimeTransaction->network;
        // prepare parameters needed by irecharge api.
        if($airtimeTransaction->network == "9mobile") {
            $serviceName = "Etisalat";
        }
        $transactionID = substr($airtimeTransaction->transaction_id, -12);
        $apiString = $this->generateAirtimeAPIString($transactionID, $airtimeTransaction->phone, $serviceName, intval($airtimeTransaction->amount), env('MODE'));
        $hash = $this->hashAPIString($apiString, env('MODE'));
        //lets now send request to irecharge api to vend the airtime for us.
        $vendResult = $this->vendAirtime($airtimeTransaction, $serviceName, intval($airtimeTransaction->amount), $airtimeTransaction->phone, $airtimeTransaction->email, $transactionID, $hash, $airtimeTransaction->service_id);
        $requestTimeStamp = date('Y-m-d H:i:s');
        if($vendResult['status'] == -1 || (1 <= $vendResult['status']) && ($vendResult['status'] <= 88)) {
            // catch error probably another cURL error or cURL executed and returned with an error.;
            // the exception has been logged to the application console. Just go ahead to update the transaction as a failed transaction and update the api requests table as well.
            \App\AirtimeTransaction::where('id', $airtimeTransaction->id)->update([
                'status'    =>  3
            ]);
            $apiRequestData = array(
                'request'               => 'API ' . $serviceName . ' N' . intval($airtimeTransaction->amount) . ' to ' . $airtimeTransaction->phone,
                'response'              =>  $vendResult['msg'],
                'request_timestamp'     =>  $requestTimeStamp,
                'response_timestamp'    =>  date('Y-m-d H:i:s'),
                'api_id'                =>  1,
                'status'                =>  0,
                'receiver'              =>  $airtimeTransaction->phone,
                'ref'                   =>  '',
                'response_hash'         =>  ''
            );
            \App\ApiRequest::create($apiRequestData);
        } else {
            \App\AirtimeTransaction::where('id', $airtimeTransaction->id)->update([
                'status'    =>  2
            ]);
        }
    }

    public function vendAirtime(\App\AirtimeTransaction $airtimeTransaction, $serviceName, $amount, $phone, $email, $transactionID, $hash, $serviceID) {
        $status = array(
            'status'   =>   0,
            'msg'      =>   'Pending.'
        );
        $result = array();
        $mode = env('MODE');
        $requestTimeStamp = date('Y-m-d H:i:s');
        $vendorURL = env('VENDOR_TEST_URL');
        if($mode == 2) {
            $vendorURL = env('VENDOR_LIVE_URL');
        }
        $url = $vendorURL . "/vend_airtime.php?vendor_code=".env('VENDOR_CODE')."&vtu_network=".$serviceName."&vtu_amount=".$amount."&vtu_number=".$phone."&vtu_email=engchris95@gmail.com&reference_id=".$transactionID."&hash=".$hash;
        Log::info('vend url');
        Log::info($url);
        try {
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 90,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0
            ));
            $request = curl_exec($ch);
            if(curl_errno($ch)) {
                Log::info('cURL error occured while trying to dispense airtime.');
                Log::error(curl_error($ch));

                $status['status'] = curl_errno($ch);
                $status['msg'] = curl_error($ch);

                \App\AirtimeTransaction::where('id', $airtimeTransaction->id)->update([
                    'status'    =>  3
                ]);
                $apiRequestData = array(
                    'request'               => 'API ' . $serviceName . ' N' . intval($airtimeTransaction->amount) . ' to ' . $airtimeTransaction->phone,
                    'response'              =>  $status['msg'],
                    'request_timestamp'     =>  $requestTimeStamp,
                    'response_timestamp'    =>  date('Y-m-d H:i:s'),
                    'api_id'                =>  1,
                    'status'                =>  0,
                    'receiver'              =>  $airtimeTransaction->phone,
                    'ref'                   =>  '',
                    'response_hash'         =>  ''
                );
                \App\ApiRequest::create($apiRequestData);
            } else {
                if ($request) {
                    $result = json_decode($this->purifyJSON($request), true);
                    Log::info('Gotten result from irecharge api');
                    Log::info($result);

                    if($result['status'] == '00') {
                        // fire event to notify user.
                        // also update the necessary tables i.e. airtime transactions and api requests tables respectively.
                        // also update wallet balance
                        $requestTimeStamp = date('Y-m-d H:i:s');
                        $apiRequestData = array(
                            'request'               => 'API ' . $serviceName . ' N' . intval($amount) . ' to ' . $phone,
                            'response'              =>  $result['message'],
                            'request_timestamp'     =>  $requestTimeStamp,
                            'response_timestamp'    =>  date('Y-m-d H:i:s'),
                            'api_id'                =>  1,
                            'status'                =>  1,
                            'receiver'              =>  $phone,
                            'ref'                   =>  $result['ref'],
                            'response_hash'         =>  $result['response_hash']
                        );
                        \App\ApiRequest::create($apiRequestData);
                        \App\Api::where('id', 1)->update([
                            'balance'   =>  $result['wallet_balance']
                        ]);
                        // try {
                        //     \Mail::to($email)->send(new \App\Mail\AirtimeVendMail($airtimeTransaction));
                        //     Log::info('Email sent');
                        // } catch(\Exception $ex) {
                        //     // mail was probably not sent to the customer.
                        //     // log this as a failed e-mail to failed email transaction table.
                        //     $failedEmailData = array(
                        //         'transaction_type'  => 'airtime',
                        //         'transaction_id'    => $airtimeTransaction->id,
                        //         'trials'            => 0
                        //     );
                        //     Log::info($ex);
                        //     \App\FailedEmail::create($failedEmailData);
                        // }
                    } else {
                        $status['status'] = $result['status'];
                        $status['msg'] = $result['message'];
                        $requestTimeStamp = date('Y-m-d H:i:s');
                        $apiRequestData = array(
                            'request'               => 'API ' . $serviceName . ' N' . intval($amount) . ' to ' . $phone,
                            'response'              =>  $result['message'],
                            'request_timestamp'     =>  $requestTimeStamp,
                            'response_timestamp'    =>  date('Y-m-d H:i:s'),
                            'api_id'                =>  1,
                            'status'                =>  0,
                            'receiver'              =>  $phone,
                            'ref'                   =>  'Failed',
                            'response_hash'         =>  'Failed'
                        );
                        \App\ApiRequest::create($apiRequestData);
                    }
                } else {
                    $status['status'] = curl_errno($ch);
                    $status['msg'] = curl_error($ch);
                    \App\AirtimeTransaction::where('id', $airtimeTransaction->id)->update([
                        'status'    =>  3
                    ]);
                }
            }
            curl_close($ch);
        } catch(\Exception $ex) {
            Log::info('Error occured while trying to start cURL to dispense airtime. cURL was probably never successfully initiated.');
            Log::error($ex);
            $status['status'] = -1;
            $status['msg'] = $ex;
            \App\AirtimeTransaction::where('id', $airtimeTransaction->id)->update([
                'status'    =>  3
            ]);
        }
        return $status;
    }

    // power manipulations
    public function getPowerServiceInfo($name) {
        Log::info('Service name = ' . $name);
        $service = \App\Service::where('name', $name)->first();

        if(!$service || empty($service)) {
            $service = "404";
        }

        Log::info('Service = ');
        Log::info($service);
        return $service;
    }

    public function generatePowerAPIString($meterNo, $transactionID, $disco, $mode = 1) {
        Log::info('generating api string for power purchase.');
        $key = env('TEST_PUBLIC_KEY');
        if($mode == 2) {
            $key = env('LIVE_PUBLIC_KEY');
        }
        Log::info(env('VENDOR_CODE')."|".$transactionID."|".$meterNo."|".$disco."|".$key);

        return env('VENDOR_CODE')."|".$transactionID."|".$meterNo."|".$disco."|".$key;
    }

    public function generatePowerVendAPIString($referenceID, $receiver, $disco, $amount, $accessToken, $mode = 1) {
        Log::info('generating api string for power vending.');
        $key = env('TEST_PUBLIC_KEY');
        if($mode == 2) {
            $key = env('LIVE_PUBLIC_KEY');
        }
        Log::info(env('VENDOR_CODE')."|".$referenceID."|".$receiver."|".$disco."|".$amount."|".$accessToken."|".$key);

        return env('VENDOR_CODE')."|".$referenceID."|".$receiver."|".$disco."|".$amount."|".$accessToken."|".$key;
    }

    public function getMeterInfo($meterNo, $referenceID, $disco, $hash, $mode = 1) {
        $resp = array(
            'status'   =>   0,
            'msg'      =>   'Pending'
        );
        $respFormat = "json";
        $vendorURL = env('VENDOR_TEST_URL');
        if($mode == 2) {
            $vendorURL = env('VENDOR_LIVE_URL');
        }
        $url = $vendorURL . "/get_meter_info.php?";
        $url .= "vendor_code=".urlencode(env('VENDOR_CODE'))."&meter=".urlencode($meterNo)."&reference_id=".urlencode($referenceID)."&disco=".urlencode($disco)."&response_format=".urlencode($respFormat)."&hash=".urlencode($hash);
        Log::info('Making api request for meter info with url:');
        Log::info($url);
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        ));
        $request = curl_exec($ch);

        if(curl_errno($ch)) {
            Log::error('Error while trying to get meter info.');
            Log::error(curl_errno($ch));
            Log::error(curl_error($ch));

            $resp['status'] = -1;
            $resp['msg'] = curl_error($ch);
        } else {
            if($request) {
                $result = json_decode($this->purifyJSON($request), true);
                Log::info('Gotten result from iRecharge API');
                Log::info($result);
                if($result['status'] != '00') {
                    $resp['status'] = $result['status'];
                    $resp['msg'] = $result['message'];
                } else {
                    $resp['status'] = $result['access_token'];
                    $resp['msg'] = $result['customer'];
                }
            }
        }
        curl_close($ch);
        Log::info('cURL result for get meter info');
        Log::info($resp['status']);
        Log::info($resp['msg']);
        return $resp;
    }

    public function handlePower($powerTransaction)
    {
        Log::info('Lets begin vending process...');
        // $referenceID, $receiver, $disco, $amount, $accessToken, 45700209468
        $serviceName = \App\Service::find($powerTransaction->service_id);
        $serviceCode = str_replace(" ", "_", $serviceName->name);
        $powerString = $this->generatePowerVendAPIString(substr($powerTransaction->transaction_id, -12), $powerTransaction->meter_num, $serviceCode, intval($powerTransaction->amount), $powerTransaction->access_token, env('MODE'));
        $powerHash = $this->hashAPIString($powerString, env('MODE'));

        // if($powerTransaction->payment_method == "WALLET") {
        //     $user = \App\User::find($powerTransaction->user_id);
        //     $current_balance = $user->wallet->balance;
        //     $new_balance = $current_balance - intval($powerTransaction->amount + $serviceName->service_charge);
        //     $user->wallet()->update(['balance' => $new_balance]);
        //     $description = $serviceName->name . ' N ' . $powerTransaction->amount_paid . ' to ' . $powerTransaction->meter_num;
        //     app('App\Http\Controllers\WalletController')->logWalletTransaction($user, $powerTransaction->amount, $new_balance, 2, $description, 1, $powerTransaction->transaction_id);
        //     $powerTransaction->update(['amount_paid' => ($powerTransaction->amount + $serviceName->service_charge)]);
        // }
        \Log::info('about to vend');
        $vendResult = $this->vendPower($powerTransaction->meter_num, substr($powerTransaction->transaction_id, -12), $serviceCode, $powerTransaction->access_token, intval($powerTransaction->amount), $powerTransaction->phone, $powerTransaction->email, $powerHash);
    }

    public function vendPower($meterNo, $referenceID, $disco, $accessToken, $amount, $phone, $email, $hash) {
        $respFormat = "json";
        $requestTimeStamp = date('Y-m-d H:i:s');
        $vendorURL = env('VENDOR_TEST_URL');
        $mode = env('MODE');
        if($mode == 2) {
            $vendorURL = env('VENDOR_LIVE_URL');
        }
        $url = $vendorURL . "/vend_power.php?";
        $url .= "vendor_code=" . urlencode(env('VENDOR_CODE')) . "&meter=" . urlencode($meterNo) . "&reference_id=" . urlencode($referenceID) . "&response_format=" . urlencode($respFormat) . "&disco=" . urlencode($disco) . "&access_token=" . urlencode($accessToken) . "&amount=" . urlencode($amount) . "&phone=" . urlencode($phone) . "&email=engchris95@gmail.com&hash=" . urlencode($hash);

        Log::info('Veding power using url: ');
        Log::info($url);

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        ));
        $request = curl_exec($ch);
        if(curl_errno($ch)) {
            Log::info('cURL error occured while trying to dispense airtime.');
            Log::error(curl_error($ch));

            $status['status'] = curl_errno($ch);
            $status['msg'] = curl_error($ch);

            $powerTransaction = \App\PowerTransaction::where('access_token', $accessToken)->first();
            $powerTransaction->update([
                'status' => 3
            ]);
            $apiID = $this->resolveServiceNameFromID($powerTransaction->service_id, 1);
            $apiRequestData = array(
                'request'               => 'API ' . $disco . ' N' . intval($amount) . ' to ' . $meterNo,
                'response'              =>  $status['msg'],
                'request_timestamp'     =>  $requestTimeStamp,
                'response_timestamp'    =>  date('Y-m-d H:i:s'),
                'api_id'                =>  $apiID,
                'status'                =>  0,
                'receiver'              =>  $meterNo,
                'ref'                   =>  'Failed',
                'response_hash'         =>  'Failed'
            );
            \App\ApiRequest::create($apiRequestData);
        } else {
            if ($request) {
                $result = json_decode($this->purifyJSON($request), true);
                Log::info('Gotten result from irecharge api');
                Log::info($result);

                if($result['status'] == '00') {
                    $powerTransaction = \App\PowerTransaction::where('access_token', $accessToken)->first();
                    $powerTransaction->update([
                        'token' =>  $result['meter_token'],
                        'units' =>  $result['units'],
                        'status' => 2
                    ]);
                    // also update api requests table.
                    $apiID = $this->resolveServiceNameFromID($powerTransaction->service_id, 1);
                    // check if there's a record for this transaction on the api requests table.
                    $apiRequestStatus = $this->checkAPITransactionStatus($apiID, $meterNo);
                    $apiRequestData = array(
                        'request'               => 'API ' . $disco . ' N' . intval($amount) . ' to ' . $meterNo,
                        'response'              =>  $result['message'],
                        'request_timestamp'     =>  $requestTimeStamp,
                        'response_timestamp'    =>  date('Y-m-d H:i:s'),
                        'api_id'                =>  $apiID,
                        'status'                =>  1,
                        'receiver'              =>  $meterNo,
                        'ref'                   =>  $result['ref'],
                        'response_hash'         =>  $result['response_hash']
                    );
                    \App\ApiRequest::create($apiRequestData);
                    \App\Api::where('id', 1)->update([
                        'balance'   =>  $result['wallet_balance']
                    ]);
                    try {
                        \Mail::to($email)->send(new \App\Mail\PowerVendMail($powerTransaction));
                    } catch(\Exception $ex) {
                        // mail was probably not sent to the customer.
                        // log this as a failed e-mail to failed email transaction table.
                        $failedEmailData = array(
                            'transaction_type'  => 'power',
                            'transaction_id'    => $powerTransaction->id,
                            'trials'            => 0
                        );
                        \Log::info($ex);
                        \App\FailedEmail::create($failedEmailData);
                    }
                    $sms = "The " . $disco ." token: " . $powerTransaction->token . " ref: " . $powerTransaction->transaction_id . ".Hope to see you again. " . env('APP_URL') . " Help-line: " .env('HELP_LINE');
                    if($mode == 2) {
                        $this->sendSMS($sms, $phone);
                    }
                } else {
                    $powerTransaction = \App\PowerTransaction::where('access_token', $accessToken)->first();
                    $powerTransaction->update([
                        'units' =>  $result['status'],
                        'status' => 3
                    ]);

                    $apiID = $this->resolveServiceNameFromID($powerTransaction->service_id, 1);
                    // check if there's a record for this transaction on the api requests table.
                    $apiRequestStatus = $this->checkAPITransactionStatus($apiID, $meterNo);
                    $apiRequestData = array(
                        'request'               => 'API ' . $disco . ' N' . intval($amount) . ' to ' . $meterNo,
                        'response'              =>  $result['message'],
                        'request_timestamp'     =>  $requestTimeStamp,
                        'response_timestamp'    =>  date('Y-m-d H:i:s'),
                        'api_id'                =>  $apiID,
                        'status'                =>  0,
                        'receiver'              =>  $meterNo,
                        'ref'                   =>  'Failed',
                        'response_hash'         =>  'Failed'
                    );
                    \App\ApiRequest::create($apiRequestData);
                }
            } else {
                $status['status'] = curl_errno($ch);
                $status['msg'] = curl_error($ch);
                $powerTransaction = \App\PowerTransaction::where('access_token', $accessToken)->first();
                $powerTransaction->update([
                    'status' => 3
                ]);
            }
        }
        curl_close($ch);
    }

    // tv manipulations
    public function generateTVAPIString($transactionID, $tvNetwork, $smartCardNo, $serviceCode, $mode = 1) {
        Log::info('generating api string for tv smart card verification');
        $key = env('TEST_PUBLIC_KEY');
        if($mode == 2) {
            $key = env('LIVE_PUBLIC_KEY');
        }
        Log::info(env('VENDOR_CODE')."|".$transactionID."|".$tvNetwork."|".$smartCardNo."|".$serviceCode."|".$key);

        return env('VENDOR_CODE')."|".$transactionID."|".$tvNetwork."|".$smartCardNo."|".$serviceCode."|".$key;
    }

    public function generateTVVendApiString($transactionID, $smartCardNo, $tvNetwork, $serviceCode, $accessToken, $mode = 1) {
        Log::info('generating api string for tv vending');
        $key = env('TEST_PUBLIC_KEY');
        if($mode == 2) {
            $key = env('LIVE_PUBLIC_KEY');
        }
        Log::info(env('VENDOR_CODE')."|".$transactionID."|".$smartCardNo."|".$tvNetwork."|".$serviceCode."|".$accessToken."|".$key);

        return env('VENDOR_CODE')."|".$transactionID."|".$smartCardNo."|".$tvNetwork."|".$serviceCode."|".$accessToken."|".$key;
    }

    public function getTVCardInfo($transactionID, $smartCardNo, $serviceCode, $tvNetwork, $hash, $respFormat, $mode) {
        // 4290975674
        $resp = array(
            'status'    =>  0,
            'msg'       => 'Pending.'
        );
        $vendorURL = env('VENDOR_TEST_URL');
        if($mode == 2) {
            $vendorURL = env('VENDOR_LIVE_URL');
        }
        $url = $vendorURL . '/get_smartcard_info.php';
        $url .= '?vendor_code=' . urlencode(env('VENDOR_CODE')) . '&smartcard_number=' . urlencode($smartCardNo) . '&service_code=' . urlencode($serviceCode) . '&reference_id=' . urlencode($transactionID) . '&tv_network=' . urlencode($tvNetwork) . '&hash=' . urlencode($hash) . '&response_format=' . urlencode($respFormat);

        Log::info('getting tv card info:');
        Log::info($url);
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        ));
        $request = curl_exec($ch);

        if(curl_errno($ch)) {
            Log::error('Error while trying to get data.');
            Log::error(curl_errno($ch));
            Log::error(curl_error($ch));

            $resp['status'] = -1;
            $resp['msg'] = curl_error($ch);
        } else {
            if($request) {
                $result = json_decode($this->purifyJSON($request), true);
                Log::info('result from verifying card info');
                Log::info($result);
                if ($result['status'] == "00") {
                    $resp['status'] = 1;
                    $resp['msg'] = $result;
                } else {
                    $resp['status'] = -1;
                }
            }
        }
        curl_close($ch);
        return $resp;
    }

    public function handleTV($tvTransaction)
    {
        $tvProvider = \App\Service::find($tvTransaction->service_id);
        $tvBundle = \App\TVBundle::find($tvTransaction->tv_bundles_id);
        $proposedAmount = intval($tvTransaction->amount_paid);
        Log::info('Proposed amount: ' . $proposedAmount);
        if(strtolower($tvBundle->name) == "StarTimes") {
            $apiString = $this->generateTVVendApiString(substr($tvTransaction->transaction_id, -12), $tvTransaction->smartcard_num, $tvProvider->name, 'StarTimes', $tvTransaction->access_token, env('MODE'));
        } else {
            $apiString = $this->generateTVVendApiString(substr($tvTransaction->transaction_id, -12), $tvTransaction->smartcard_num, $tvProvider->name, $tvBundle->code, $tvTransaction->access_token, env('MODE'));
        }

        $hash = $this->hashAPIString($apiString, env('MODE'));

        if(strtolower($tvBundle->name) == "startimes") {
            $startimesAmount = $tvTransaction->amount;
            Log::info('StarTimes amount: ' . $startimesAmount);
            $vendTV = $this->vendTV($tvTransaction, $tvProvider->name, $tvBundle->code, $startimesAmount, $hash, $tvBundle->name);
        } else {
            $vendTV = $this->vendTV($tvTransaction, $tvProvider->name, $tvBundle->code, 0, $hash, $tvBundle->name);
        }
    }

    public function vendTV(\App\TVTransaction $tvTransaction, $tvProvider, $serviceCode, $startimesAmount = 0, $hash, $package) {
        $status = array();
        $respFormat = "json";
        $requestTimeStamp = date('Y-m-d H:i:s');
        $mode = env('MODE');
        $vendorURL = env('VENDOR_TEST_URL');
        if($mode == 2) {
            $vendorURL = env('VENDOR_LIVE_URL');
        }
        $url = $vendorURL . "/vend_tv.php";
        if($startimesAmount === 0) {
            $url .= "?vendor_code=".urlencode(env('VENDOR_CODE'))."&smartcard_number=".urlencode($tvTransaction->smartcard_num)."&reference_id=".urlencode(substr($tvTransaction->transaction_id, -12))."&response_format=".urlencode($respFormat)."&tv_network=".urlencode($tvProvider)."&access_token=".urlencode($tvTransaction->access_token)."&service_code=".urlencode($serviceCode)."&phone=".urlencode($tvTransaction->phone)."&email=engchris95@gmail.com&hash=".urlencode($hash);
        } else {
            Log::info('using startimes URL');
            $url .= "?vendor_code=".urlencode(env('VENDOR_CODE'))."&smartcard_number=".urlencode($tvTransaction->smartcard_num)."&reference_id=".urlencode(substr($tvTransaction->transaction_id, -12))."&response_format=".urlencode($respFormat)."&tv_network=".urlencode($tvProvider)."&access_token=".urlencode($tvTransaction->access_token)."&service_code=".urlencode($serviceCode)."&startimes_amount=".urlencode($startimesAmount)."&phone=".urlencode($tvTransaction->phone)."&email=engchris95@gmail.com&hash=".urlencode($hash);
        }
        Log::info('Vend request string');
        Log::info($url);

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        ));
        $request = curl_exec($ch);
        if(curl_errno($ch)) {
            Log::info('cURL error occured while trying to dispense tv.');
            Log::error(curl_error($ch));

            $status['status'] = curl_errno($ch);
            $status['msg'] = curl_error($ch);

            $tvTransaction->update([
                'status' => 3
            ]);

            $apiID = $this->resolveServiceNameFromID($tvTransaction->service_id, 1);
            // check if there's a record for this transaction on the api requests table.
            $apiRequestStatus = $this->checkAPITransactionStatus($apiID, $tvTransaction->smartcard_num);
            $apiRequestData = array(
                'request'               => 'API ' . $tvProvider . $package . ' to ' . $tvTransaction->smartcard_num,
                'response'              =>  $status['message'],
                'request_timestamp'     =>  $requestTimeStamp,
                'response_timestamp'    =>  date('Y-m-d H:i:s'),
                'api_id'                =>  $apiID,
                'status'                =>  0,
                'receiver'              =>  $tvTransaction->smartcard_num,
                'ref'                   =>  'Failed',
                'response_hash'         =>  'Failed'
            );
            \App\ApiRequest::create($apiRequestData);
        } else {
            if ($request) {
                $result = json_decode($this->purifyJSON($request), true);
                Log::info('Gotten result from irecharge api');
                Log::info($result);

                if($result['status'] == '00') {
                    $tvTransaction->update([
                        'status' => 2
                    ]);
                    // also update api requests table.
                    $apiID = $this->resolveServiceNameFromID($tvTransaction->service_id, 1);
                    // check if there's a record for this transaction on the api requests table.
                    $apiRequestStatus = $this->checkAPITransactionStatus($apiID, $tvTransaction->smartcard_num);

                    $apiRequestData = array(
                        'request'               => 'API ' . $tvProvider . $package . ' to ' . $tvTransaction->smartcard_num,
                        'response'              =>  $result['message'],
                        'request_timestamp'     =>  $requestTimeStamp,
                        'response_timestamp'    =>  date('Y-m-d H:i:s'),
                        'api_id'                =>  $apiID,
                        'status'                =>  1,
                        'receiver'              =>  $tvTransaction->smartcard_num,
                        'ref'                   =>  random_int(11111111, 9999999999),
                        'response_hash'         =>  $result['response_hash']
                    );
                    \App\ApiRequest::create($apiRequestData);
                    \App\Api::where('id', 1)->update([
                        'balance'   =>  $result['wallet_balance']
                    ]);
                    try {
                        \Mail::to($tvTransaction->email)->send(new \App\Mail\TvVendMail($tvTransaction));
                    } catch(\Exception $ex) {
                        // mail was probably not sent to the customer.
                        // log this as a failed e-mail to failed email transaction table.
                        $failedEmailData = array(
                            'transaction_type'  => 'tv',
                            'transaction_id'    => $tvTransaction->id,
                            'trials'            => 0
                        );
                        \Log::info($ex);
                        \App\FailedEmail::create($failedEmailData);
                    }
                    $sms = "Your " . $package ." subscription was successful. ref: " . $tvTransaction->transaction_id . ".Hope to see you again. " . env('APP_URL') . " Help-line: ".env('HELP_LINE');
                    if($mode == 2) {
                        $this->sendSMS($sms, $tvTransaction->phone);
                    }
                } else {
                    $tvTransaction->update([
                        'status' => 3
                    ]);

                    $apiID = $this->resolveServiceNameFromID($tvTransaction->service_id, 1);
                    // check if there's a record for this transaction on the api requests table.
                    $apiRequestStatus = $this->checkAPITransactionStatus($apiID, $tvTransaction->smartcard_num);
                    $apiRequestData = array(
                        'request'               => 'API ' . $tvProvider . $package . ' to ' . $tvTransaction->smartcard_num,
                        'response'              =>  $result['message'],
                        'request_timestamp'     =>  $requestTimeStamp,
                        'response_timestamp'    =>  date('Y-m-d H:i:s'),
                        'api_id'                =>  $apiID,
                        'status'                =>  0,
                        'receiver'              =>  $tvTransaction->smartcard_num,
                        'ref'                   =>  'Failed',
                        'response_hash'         =>  'Failed'
                    );
                    \App\ApiRequest::create($apiRequestData);
                }
            } else {
                $status['status'] = curl_errno($ch);
                $status['msg'] = curl_error($ch);
                $tvTransaction->update([
                    'status' => 3
                ]);
            }
        }
        curl_close($ch);
        return $status;
    }

    // data manipulations
    public function generateDataSmileAPIString($receiver, $mode = 1) {
        Log::info('generating api string for smile purchase.');
        $key = env('TEST_PUBLIC_KEY');
        if($mode == 2) {
            $key = env('LIVE_PUBLIC_KEY');
        }
        Log::info(env('VENDOR_CODE')."|".$receiver."|".$key);

        return env('VENDOR_CODE')."|".$receiver."|".$key;
    }

    public function verifySmileInfo ($receiver, $mode = 1) {
        $combinedString = $this->generateDataSmileAPIString($receiver);
        $hash = $this->hashAPIString($combinedString);

        $resp = array(
            'status'    =>  0,
            'msg'       => 'Pending.'
        );
        $vendorURL = env('VENDOR_TEST_URL');
        if($mode == 2) {
            $vendorURL = env('VENDOR_LIVE_URL');
        }
        $url = $vendorURL . '/get_smile_info.php';
        $respFormat = 'json';
        $url .= '?vendor_code=' . urlencode(env('VENDOR_CODE')) . '&receiver=' . urlencode($receiver) . '&hash=' . urlencode($hash) . '&response_format=' . urlencode($respFormat);

        Log::info('getting smile info:');
        Log::info($url);
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        ));
        $request = curl_exec($ch);

        if(curl_errno($ch)) {
            Log::error('Error while trying to get data.');
            Log::error(curl_errno($ch));
            Log::error(curl_error($ch));

            $resp['status'] = -1;
            $resp['msg'] = curl_error($ch);
        } else {
            \Log::info($request);
            if($request) {
                $result = json_decode($this->purifyJSON($request), true);
                Log::info('result from verifying card info');
                Log::info($result);
                $resp['status'] = 1;
                $resp['msg'] = $result;
            }
        }
        curl_close($ch);
        return $resp;
    }

    public function generateDataAPIString($transactionID, $receiver, $serviceName, $data, $mode = 1) {
        Log::info('generating api string for data purchase.');
        $key = env('TEST_PUBLIC_KEY');
        if($mode == 2) {
            $key = env('LIVE_PUBLIC_KEY');
        }
        Log::info(env('VENDOR_CODE')."|".$transactionID."|".$receiver."|".$serviceName."|".$data."|".$key);

        return env('VENDOR_CODE')."|".$transactionID."|".$receiver."|".$serviceName."|".$data."|".$key;
    }

    public function handleData($dataTransaction)
    {
        Log::info('Lets begin dispensing...');
        
        $requestedBundle = \App\DataBundle::find($dataTransaction->data_bundles_id);

        Log::info($requestedBundle);

        \App\DataTransaction::find($dataTransaction->id)->update([
            'status'    =>  1
        ]);

        $serviceName = $this->resolveServiceNameFromID($dataTransaction->service_id);
        $transactionID = substr($dataTransaction->transaction_id, -12);

        $apiString = $this->generateDataAPIString($transactionID, $dataTransaction->phone, $serviceName, $requestedBundle->code, env('MODE'));
        $hash = $this->hashAPIString($apiString, env('MODE'));
        Log::info('Requesting data for transaction: ');
        Log::info($dataTransaction);
        
        $vendResult = $this->vendData($dataTransaction, $serviceName, $requestedBundle->code, $dataTransaction->phone, $dataTransaction->email, $transactionID, $hash, $dataTransaction->service_id, $requestedBundle->name);
        $requestTimeStamp = date('Y-m-d H:i:s');
        if((1 <= $vendResult['status']) && ($vendResult['status'] <= 88)) {
            \App\DataTransaction::find($dataTransaction->id)->update([
                'status'    =>  3
            ]);
            $apiID = $this->resolveServiceNameFromID($dataTransaction->service_id, 1);
            $apiRequestData = array(
                'request'               => 'API ' . $requestedBundle->name . ' to ' . $dataTransaction->phone,
                'response'              =>  $vendResult['msg'],
                'request_timestamp'     =>  $requestTimeStamp,
                'response_timestamp'    =>  date('Y-m-d H:i:s'),
                'api_id'                =>  $apiID,
                'status'                =>  0,
                'receiver'              =>  $dataTransaction->phone,
                'ref'                   =>  '',
                'response_hash'         =>  ''
            );
            \App\ApiRequest::create($apiRequestData);
        } else {
            \App\DataTransaction::find($dataTransaction->id)->update([
                'status'    =>  2
            ]);
        }
    }

    public function vendData(\App\DataTransaction $dataTransaction, $serviceName, $code, $phone, $email, $transactionID, $hash, $serviceID, $bundleName) {
        $status = array(
            'status'   =>   0
        );
        $result = array();
        $mode = env('MODE');
        $requestTimeStamp = date('Y-m-d H:i:s');
        $vendorURL = env('VENDOR_TEST_URL');
        if($mode == 2) {
            $vendorURL = env('VENDOR_LIVE_URL');
        }
        $url = $vendorURL . "/vend_data.php?vendor_code=".env('VENDOR_CODE')."&vtu_network=".$serviceName."&vtu_data=".$code."&vtu_number=".$phone."&vtu_email=engchris95@gmail.com&reference_id=".$transactionID."&hash=".$hash;
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        ));
        $request = curl_exec($ch);
        if(curl_errno($ch)) {
            Log::info('cURL error occured while trying to dispense data.');
            Log::error(curl_error($ch));

            $status['status'] = curl_errno($ch);
            $status['msg'] = curl_error($ch);

            \App\DataTransaction::find($dataTransaction->id)->update([
                'status'    =>  3
            ]);
            $requestedBundle = \App\DataBundle::find($dataTransaction->data_bundles_id);
            $apiID = $this->resolveServiceNameFromID($serviceID, 1);
            $apiRequestData = array(
                'request'               => 'API ' . $requestedBundle->name . ' to ' . $dataTransaction->phone,
                'response'              =>  $status['msg'],
                'request_timestamp'     =>  $requestTimeStamp,
                'response_timestamp'    =>  date('Y-m-d H:i:s'),
                'api_id'                =>  $apiID,
                'status'                =>  0,
                'receiver'              =>  $dataTransaction->phone,
                'ref'                   =>  '',
                'response_hash'         =>  ''
            );
            \App\ApiRequest::create($apiRequestData);
        } else {
            if ($request) {
                $result = json_decode($this->purifyJSON($request), true);
                Log::info('Gotten result from irecharge api');
                Log::info($result);

                if($result['status'] == '00') {
                    // fire event to notify user.
                    // also update the necessary tables i.e. data transactions and api requests tables respectively.
                    // also update wallet balance
                    $requestTimeStamp = date('Y-m-d H:i:s');
                    $apiID = $this->resolveServiceNameFromID($serviceID, 1);
                    $apiRequestData = array(
                        'request'               => 'API ' . $bundleName . ' to ' . $phone,
                        'response'              =>  $result['message'],
                        'request_timestamp'     =>  $requestTimeStamp,
                        'response_timestamp'    =>  date('Y-m-d H:i:s'),
                        'api_id'                =>  $apiID,
                        'status'                =>  0,
                        'receiver'              =>  $phone,
                        'ref'                   =>  $result['ref'],
                        'response_hash'         =>  $result['response_hash']
                    );
                    \App\ApiRequest::create($apiRequestData);
                    \App\Api::where('id', 1)->update([
                        'balance'   =>  $result['wallet_balance']
                    ]);
                    // try {
                    //     \Mail::to($email)->send(new \App\Mail\DataVendMail($dataTransaction));
                    // } catch(\Exception $ex) {
                    //     // mail was probably not sent to the customer.
                    //     // log this as a failed e-mail to failed email transaction table.
                    //     $failedEmailData = array(
                    //         'transaction_type'  => 'data',
                    //         'transaction_id'    => $dataTransaction->id,
                    //         'trials'            => 0
                    //     );
                    //     \Log::info($ex);
                    //     \App\FailedEmail::create($failedEmailData);
                    // }
                } else {
                    $status['status'] = $result['status'];
                    $status['msg'] = $result['message'];
                    \App\DataTransaction::find($dataTransaction->id)->update([
                        'status'    =>  3
                    ]);
                }
            } else {
                $status['status'] = curl_errno($ch);
                $status['msg'] = curl_error($ch);
                \App\DataTransaction::find($dataTransaction->id)->update([
                    'status'    =>  3
                ]);
            }
        }
        curl_close($ch);

        return $status;
    }

}
