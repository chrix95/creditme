<?php

namespace App\Http\Controllers\Apis;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Apis\UserControllerExtension;
use App\Http\Controllers\UtilityController;

class UserController extends Controller
{
    public $utility;
    public $user;

    public function __construct(UtilityController $utility, UserControllerExtension $user) {
        $this->utility = $utility;
        $this->user = $user;
    }

    public function registerUser (Request $request) {
        $status = '00';
        $field_name = 'data';
        $resp = array();
        $data = array (
            'name'      =>  htmlentities(strip_tags(trim($request->name))),
            'email'     =>  htmlentities(strip_tags(trim($request->email))),
            'phone'     =>  htmlentities(strip_tags(trim($request->phone))),
            'password'  =>  htmlentities(strip_tags(trim($request->password))),
            'password_confirmation'  =>  htmlentities(strip_tags(trim($request->password_confirmation))),
            'passcode'  =>  htmlentities(strip_tags(trim($request->passcode)))
        );
        $validator = \Validator::make($data, [
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users',
            'password'  => 'required|string|min:6|confirmed',
            'phone'     => 'required|digits:11|unique:users',
            'passcode'  =>  'required|string'
        ], [
            'name.required'     => 'First name field is required',
            'name.string'       => 'First name cannot contain invalid characters. Please check and try again.',
            'name.max'          => 'First name cannot be more than 200 characters.',
            'email.required'    => 'E-mail field is required.',
            'email.email'       => 'Invalid e-mail, please enter a valid e-mail address!',
            'email.unique'      => 'It appears we already have an account with that email!',
            'password.required' => 'Password field is required',
            'password.string'   => 'Password cannot contain special characters!',
            'password.confirmed'=> 'Passwords do not match.',
            'phone.required'    => 'Phone number is required.',
            'phone.unique'      => 'It appears we already have an account with that phone!',
            'passcode.required' => 'Passcode is required.',
            'passcode.string'   => 'Passcode cannot contain invalid characters. Please check and try again.'
        ]);

        if($validator->fails()) {
            $status = '05';
            return $this->utility->response($status, 'error', $validator->errors());
        }

        if ($this->utility->verifyAPIPasscode($data['email']) !== $request->passcode) {
            $status = '01';
        } else {
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'phone'     => $data['phone']
            ]);

            $user->wallet()->create(['balance' => 0.0]);
            $resp['user'] = $user;
        }

        return $this->utility->response($status, $field_name, $resp);
    }
    
    public function signinUser (Request $request) {
        $status = '00';
        $field_name = 'data';
        $resp = array();
        $credentials = $request->only('email', 'password');

        if (\Auth::attempt($credentials)) {
            // Authentication passed...
            $status = '00';
            $resp['user'] = \Auth::user();
        } else {
            $status = '03';
        }
        return $this->utility->response($status, $field_name, $resp);
    }

    public function fund_user_wallet(Request $request, $email = null) {
        $status = '00';
        $field_name = 'data';
        $resp = array();
        $data = array(
            'email'         =>  $request->email,
            'payment_ref'   =>  $request->payment_ref,
            'amount'        =>  $request->amount,
            'passcode'      =>  $request->passcode,
        );

        $validator = \Validator::make($data, [
            'email'         =>  'required|email',
            'payment_ref'   =>  'required|string',
            'amount'        =>  'required|integer',
            'passcode'      =>  'required|string'
        ]);

        if($validator->fails()) {
            $status = '05';
            return $this->utility->response($status, 'error', $validator->errors());
        }
        
        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            $status = '03';
            return $this->utility->response($status, $field_name, []);
        }

        $combinedString = $user->email . "|" . $data['payment_ref'];
        if ($this->utility->verifyAPIPasscode($combinedString) !== $request->passcode) {
            $status = '01';
        } else {        
            // verify the payment
            // $transaction_reference = $this->generate_transaction_reference();
            if(env('PAYMENT_MODE') == 1) {
                $verification_status = $this->user->verifyPayment($data['amount'], $data['payment_ref'], env('MODE'));
            } else if(env('PAYMENT_MODE') == 2) {
                $verification_status = $this->user->verifyRavePayment($data['amount'], $data['payment_ref'], env('MODE'));
            } else {
                $verification_status = $this->user->verifyGladePayment($data['amount'], $data['payment_ref'], env('MODE'));
            }
    
            $amount_to_credit = $data['amount'];
            $new_wallet_balance = $user->wallet->balance + $data['amount'];
    
            if($verification_status[0]['status'] == -1)
            {
                // cURL error
                // log as failed transaction
                $status = '10';
                $resp['status'] = $verification_status[0]['status'];
                $resp['message'] = 'Payment verification failed to verify wallet funding.';
                $this->user->log_wallet_transaction($user, $data['amount'], $user->wallet->balance, 1, $resp['message'], 0, $data['payment_ref']);
                try {
                    \Mail::to($user->email)->send(new \App\Mail\PersonalWalletFunding($data['amount'], $user->wallet->balance, $data['payment_ref'], 1, 'Payment verification request failed. The system could not complete the request.'));
                } catch(\Exception $ex) {
                    $failedEmailData = array(
                        'transaction_type'  => 'wallet',
                        'transaction_id'    => $data['payment_ref'],
                        'trials'            => 0
                    );
                    \Log::info($ex);
                    \App\FailedEmail::create($failedEmailData);
                }
            }
            else if($verification_status[0]['status'] == 503)
            {
                $status = '10';
                $resp['status'] = $verification_status[0]['status'];
                $resp['message'] = 'Payment verification was unable to confirm payment.';
                $this->user->log_wallet_transaction($user, $data['amount'], $user->wallet->balance, 1, $resp['message'], 0, $data['payment_ref']);
                try {
                    \Mail::to($user->email)->send(new \App\Mail\PersonalWalletFunding($data['amount'], $user->wallet->balance, $data['payment_ref'], 2, 'A provider error occured trying to verify your payment.'));
                } catch(\Exception $ex) {
                    $failedEmailData = array(
                        'transaction_type'  => 'wallet',
                        'transaction_id'    => $data['payment_ref'],
                        'trials'            => 0
                    );
                    \Log::info($ex);
                    \App\FailedEmail::create($failedEmailData);
                }
            }
            else if($verification_status[0]['status'] == 404)
            {
                $status = '09';
                $resp['status'] = $verification_status[0]['status'];
                $resp['message'] = 'Unfortunately, we could not find any transaction related to your request.';
                $this->user->log_wallet_transaction($user, $data['amount'], $user->wallet->balance, 1, $resp['message'], 0, $data['payment_ref']);
                try {
                    \Mail::to($user->email)->send(new \App\Mail\PersonalWalletFunding($data['amount'], $user->wallet->balance, $data['payment_ref'], 3, 'We could not find any payment with your transaction reference.'));
                } catch(\Exception $ex) {
                    $failedEmailData = array(
                        'transaction_type'  => 'wallet',
                        'transaction_id'    => $data['payment_ref'],
                        'trials'            => 0
                    );
                    \Log::info($ex);
                    \App\FailedEmail::create($failedEmailData);
                }
            }
            else if($verification_status[0]['status'] == 419)
            {
                $status = '08';
                $resp['status'] = $verification_status[0]['status'];
                $resp['message'] = 'Unfortunately, our servers encountered an error trying to validate your payment status. Please contact our customer support lines with your transaction reference for help.';
                $this->user->log_wallet_transaction($user, $data['amount'], $user->wallet->balance, 1, 'Fradulent transaction detected on our system. Please contact our customer support lines with your transaction reference for help.', 0, $data['payment_ref']);
                try {
                    \Mail::to($user->email)->send(new \App\Mail\PersonalWalletFunding($data['amount'], $user->wallet->balance, $data['payment_ref'], 3, $resp['message']));
                } catch(\Exception $ex) {
                    $failedEmailData = array(
                        'transaction_type'  => 'wallet',
                        'transaction_id'    => $data['payment_ref'],
                        'trials'            => 0
                    );
                    \Log::info($ex);
                    \App\FailedEmail::create($failedEmailData);
                }
            }
            else if($verification_status[0]['status'] == 100)
            {
                $resp['status'] = $verification_status[0]['status'];
                $resp['message'] = 'Wallet funding successful.';
                $resp['new_balance'] = $new_wallet_balance;
                $resp['transaction'] = $this->user->log_wallet_transaction($user, $data['amount'], $new_wallet_balance, 1, $resp['message'], 1, $data['payment_ref']);
                $amount_to_credit = $data['amount'];
                $this->user->update_user_wallet_balance($user->id, $new_wallet_balance);
                try {
                    \Mail::to($user->email)->send(new \App\Mail\PersonalWalletFunding($data['amount'], $new_wallet_balance, $data['payment_ref'], 4, 'Wallet funding successful'));
                } catch(\Exception $ex) {
                    $failedEmailData = array(
                        'transaction_type'  => 'wallet',
                        'transaction_id'    => $data['payment_ref'],
                        'trials'            => 0
                    );
                    \Log::info($ex);
                    \App\FailedEmail::create($failedEmailData);
                }
                try {
                    \Mail::to(env('DEFAULT_EMAIL_ADDRESS'))->send(new \App\Mail\WalletFunding($user, $data['amount'], $new_wallet_balance, $data['payment_ref'], 4, 'Wallet funding successful'));
                } catch(\Exception $ex) {
                    $failedEmailData = array(
                        'transaction_type'  => 'wallet',
                        'transaction_id'    => $data['payment_ref'],
                        'trials'            => 0
                    );
                    \Log::info($ex);
                    \App\FailedEmail::create($failedEmailData);
                }
            }
        }
        return $this->utility->response($status, $field_name, $resp);
    }
    
    public function create_voucher (Request $request) {
        $status = '00';
        $field_name = 'data';
        $resp = array();
        $data = array(
            'amount'        =>  $request->amount,
            'expiry'        =>  $request->expiry,
            'passcode'      =>  $request->passcode
        );

        $validator = \Validator::make($data, [
            'amount'        =>  'required|integer',
            'expiry'        =>  'required|date',
            'passcode'      =>  'required|string'
        ]);

        if($validator->fails()) {
            $status = '05';
            return $this->utility->response($status, 'error', $validator->errors());
        }

        $combinedString = $data['amount'] . "|" . $data['expiry'];
        if ($this->utility->verifyAPIPasscode($combinedString) !== $request->passcode) {
            $status = '01';
        } else {
            $voucher = $this->generateVoucher();
            $data['voucher'] = $voucher;
            $data['balance'] = $data['amount'];
            $transaction = \App\Voucher::create($data);
            $status = '00';
            $resp['data'] = $transaction;
        }

        return $this->utility->response($status, $field_name, $resp);
    }

    private function generateVoucher() {
        $id = random_int(000000000, 999999999999);
        $length = strlen((string)$id);
        if($length < 12 || $length > 12) {
            $id = $this->generateVoucher();
        }
        $transactionID = $id;
        $existVoucherCode =\App\Voucher::where('voucher', $transactionID)->first();
        if ($existVoucherCode) {
            $this->generateVoucher();
        }
        return $transactionID;
    }

    public function delete_voucher (Request $request, $id) {
        $status = '00';
        $field_name = 'data';
        $resp = array();
        $voucher = \App\Voucher::find($id);
        if (!$voucher) {
            $status = '06';
        } else {
            $voucher->delete();
            $status = '00';
        }
        return $this->utility->response($status, $field_name, $resp);
    }
    
}
