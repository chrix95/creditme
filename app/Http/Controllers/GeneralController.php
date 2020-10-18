<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class GeneralController extends Controller
{
    public function index () {
        if (\Auth::check()) {
            $transactions = \App\WalletTransaction::where('wallet_id', \Auth::user()->wallet->id)->latest()->get();
            $transactionHistory = array();
            $count = count($transactions);
            if ($count >= 3) {
                for ($i=($count - 3); $i < $count ; $i++) {
                    $data = array(
                        'transaction_type'          =>  $transactions[$i]['transaction_type'],
                        'transaction_description'   =>  $transactions[$i]['transaction_description'],
                        'transaction_amount'   =>  $transactions[$i]['transaction_amount'],
                        'status'   =>  $transactions[$i]['status'],
                        'created_at'   =>  $transactions[$i]['created_at']->diffForHumans()
                    );
                    array_push($transactionHistory, $data);
                }
                $transactionHistory = array_reverse($transactionHistory);
            } else {
                $transactionHistory = $transactions;
            }
            return view('welcome', compact('transactionHistory'));
        } else {
            return view('welcome');
        }
    }
    
    public function contact () {
        return view('contact');
    }

    public function about () {
        return view('about');
    }

    public function terms () {
        return view('terms');
    }

    public function services () {
        return view('services');
    }

    public function subscribeSubmit (Request $request) {
        $data = array(
            'name'  =>  $request->name,
            'email' =>  $request->email,
            'type'  =>  $request->type
        );
        try {
            \Mail::to(env('DEFAULT_EMAIL_ADDRESS'))->send(new \App\Mail\SubscribeMail($data));
            Session::flash('success', 'Thank you for subscribing, our customer service will reach out to you shortly.');
        } catch(\Exception $ex) {
            \Log::info($ex);
            Session::flash('error', 'Oops!, an error occured while submitting your request. Kindly try again.');
        }
        return redirect()->back();
    }

    public function contactSubmit (Request $request) {
        $data = array(
            'name'          =>  htmlentities(strip_tags(trim($request->name))),
            'email'         =>  htmlentities(strip_tags(trim($request->email))),
            'phone'         =>  htmlentities(strip_tags(trim($request->name))),
            'message'       =>  htmlentities(strip_tags(trim($request->message)))
        );

        $validator = \Validator::make($data, [
            'name'      =>  'required|string',
            'email'     =>  'required|email',
            'phone'     =>  'required|string',
            'message'   =>  'required|string',
        ], [
            'name.required'     =>  'Name field is required. Please fill in your name.',
            'email.required'    =>  'E-Mail field is required. Please enter your e-mail address.',
            'email.email'       =>  'A valid e-mail is required as this will be our primary mode of contacting you for feedback.',
            'phone.required'  =>  'Your phone number is important to easy support. Kindly write your message to proceed',
            'message.required'  =>  'Your forgot to type a message. Kindly write your message to proceed'
        ]);

        if($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $message = \App\ContactMessage::create($data);
        \Session::flash('success', 'Your message has been received and will be duly treated. We will contact you as soon as possible if need be.');
        \Mail::to(env('DEFAULT_EMAIL_ADDRESS'))->send(new \App\Mail\ContactMessage($message));

        return redirect()->back();
    }
}
