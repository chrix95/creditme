<?php

namespace App\Http\Controllers\Apis;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class UserControllerExtension extends Controller
{
	public function generate_transaction_reference() {
		$random_string_length = 10;
		$characters = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$string = '';
		$max = strlen($characters) - 1;
		for ($i = 0; $i < $random_string_length; $i++)
		{
			$string .= $characters[mt_rand(0, $max)];
		}
		return $string;
	}
	
	public function log_wallet_transaction($user, $amount_entered, $new_balance, $transaction_type, $description, $transaction_status, $transaction_reference)
    {
        $wallet_transaction = $user->wallet->transactions()->create([
            'transaction_amount'        => $amount_entered,
            'current_balance'           => $user->wallet->balance,
            'new_balance'               => $new_balance,
            'transaction_type'          => $transaction_type,
            'transaction_description'   => $description,
            'status'                    => $transaction_status,
            'transaction_reference'     => $transaction_reference
        ]);
        return $wallet_transaction;
	}
	
	public function update_user_wallet_balance($user_id, $amount) {
        $update = false;
        $user = $this->is_user($user_id);
        if(!is_int($user)) {
            $user->wallet()->update(['balance' => $amount]);
            $update = !$update;
        }
        return $update;
	}

	public function is_user($user_id) {
        $user = \App\User::find($user_id);
        if(!$user) {
            $user = 404;
        }
        return $user;
    }
	
	public function verifyPayment($amountToPay, $paymentReference, $mode = 1) {
        Log::info('lets try to verify payment to fund wallet on paystack');
        $amount = 0.0;
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

            // verification failed
            $verified = -1;
        } else {
            if ($request) {
                $result = json_decode($request, true);
                Log::info('result from paystack');
                Log::info($result);
                if($result["data"] && $result["data"]["status"] == "success") {
                    if ($amountToPay == ($result["data"]["amount"] / 100)) {
                        // at this point, payment has been verified.
                        $verified = 100;
                        $amount = $result["data"]["amount"] / 100;
                    } else {
                        $verified = 419;
                    }
                } else {
                    // $resp['msg'] = 'Transaction not found!';
                    $verified = 404;
                }
            } else {
                // $resp['msg'] = 'Unable to verify transaction!';
                $verified = 503;
            }
        }
        curl_close($ch);
        return array(['status' => $verified, 'amount' => $amount]);
    }

    public function verifyRavePayment($amountToPay, $ref, $mode = 1)
    {
        $resp = 0;
        $amount = 0;
        $data = array(
            'txref' => $ref,
            'SECKEY' => env('FLUTTER_WAVE_SANDBOX_SECRET_KEY')
        );

        $url = "https://ravesandboxapi.flutterwave.com/flwv3-pug/getpaidx/api/v2/verify";

        if ($mode == 2) {
            $url = "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify";
            $data['SECKEY'] = env('FLUTTER_WAVE_LIVE_SECRET_KEY');
        }

        if($mode == 1) {
            try {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => "txref=".$data['txref']."&SECKEY=".$data['SECKEY'],
                    CURLOPT_HTTPHEADER => array(
                        "cache-control: no-cache",
                        "content-type: application/x-www-form-urlencoded",
                    ),
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0
                ));

                $response   = curl_exec($curl);
                $err        = curl_error($curl);
                $obj        = json_decode($response);

                if ($err) {
                    $resp = curl_errno($curl);
                    \Log::info('curl error after executing curl:');
                    \Log::info(curl_error($curl));
                    $resp = -1;
                } else {
                    if ($obj->status === "success" && $obj->data->chargecode === "00") {
                        if ($amountToPay == $obj->data->amount) {
                            $amount = $obj->data->amount;
                            $resp = 100;
                        } else {
                            $resp = 419;
                        }
                    } else {
                        $resp = 404;
                    }
                }
                curl_close($curl);
            } catch (\Exception $ex) {
                \Log::info('error occured trying to initiate curl to validate wallet funding');
                \Log::info($ex->getMessage());
            }
        } else {
            // verification for live is a bit different
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "txref=".$data['txref']."&SECKEY=".$data['SECKEY'],
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "content-type: application/x-www-form-urlencoded",
                    "postman-token: 74d44d1a-946f-26b8-7506-8aba0e3c1a3b"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            \Log::info('response from flutterwave');
            \Log::info($response);
            \Log::info('error details');
            \Log::info($err);
            \Log::info('response gettings');
            $obj = json_decode($response);
            \Log::info($obj->status);
            \Log::info($obj->data->amount);

            if ($err) {
                $resp = curl_errno($err);
                $resp = -1;
            } else {
                // Verification upon no errors
                if ($obj->status === "success" && $obj->data->chargecode === "00") {
                    //confirm that the amount is the amount you wanted to charge
                    if ($amountToPay == $obj->data->amount) {
                        $amount = $obj->data->amount;
                        $resp = 100;
                    } else {
                        $resp = 419;
                    }
                } else {
                    $resp = 400;
                }
            }
            curl_close($curl);
        }

        return array(['status' => $resp, 'amount' => $amount]);
    }

    public function verifyGladePayment($amountToPay, $paymentReference, $mode = 1) {
        Log::info('lets try to verify payment on gladepay');
        $resp = 0;
        $result = array();
        if($mode == 2) {
            $url = 'https://api.gladepay.com/payment';
            $key = env('GLADE_PAY_LIVE_MERCHANT_KEY');
            $id = env('GLADE_PAY_LIVE_MERCHANT_ID');
        } else {
            $url = 'https://demo.api.gladepay.com/payment';
            $key = env('GLADE_PAY_TEST_MERCHANT_KEY');
            $id = env('GLADE_PAY_TEST_MERCHANT_ID');
        }
        // set post fields
        $request_data = [
            "action" => "verify",
            "txnRef" => $paymentReference
        ];
        $fields = json_encode($request_data);
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_HTTPHEADER => array(
              "key: ". $key,
              "mid: " . $id, 
              "Content-Type: application/json"
            ),
        ));
        $request = curl_exec($ch);

        if(curl_errno($ch)) {
            // $resp['msg'] = curl_error($ch);
            $verified = curl_errno($ch);
            Log::info('cURL error occured while trying to verify payment.');
            Log::error(curl_error($ch));
            $resp = -1;
        } else {
            if ($request) {
                $result = (array) json_decode($request, true);
                \Log::info($result);
                if($result['txnStatus'] == "successful" && $result['status'] == 200) {
                    // at this point, payment has been verified.
                    Log::info('Payment successfully verified.');
                    if ($amountToPay == $result['chargedAmount']) {
                        $amount = $result['chargedAmount'];
                        $resp = 100;
                    } else {
                        $resp = 419;
                    }
                } else {
                    // $resp['msg'] = 'Transaction not found!';
                    $resp = 404;
                }
            } else {
                // $resp['msg'] = 'Unable to verify transaction!';
                $resp = 503;
            }
        }
        curl_close($ch);
        return array(['status' => $resp, 'amount' => $amount]);
	}
	
}