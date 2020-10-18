<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    public function profile()
    {
        $airtimeTransactions    = \App\AirtimeTransaction::where('user_id', \Auth::id())->orderBy('date_created', 'desc')->paginate(15);
        $dataTransactions       = \App\DataTransaction::where('user_id', \Auth::id())->orderBy('date_created', 'desc')->paginate(15);
        $powerTransactions      = \App\PowerTransaction::where('user_id', \Auth::id())->orderBy('date_created', 'desc')->paginate(15);
        $tvTransactions         = \App\TVTransaction::where('user_id', \Auth::id())->orderBy('date_created', 'desc')->paginate(15);
        $wallet_transactions    = \App\WalletTransaction::where('wallet_id', \Auth::user()->wallet->id)->orderBy('created_at', 'desc')->paginate(20);
        return view('profile', [
            'airtimeTransactions'   =>  $airtimeTransactions,
            'dataTransactions'      =>  $dataTransactions,
            'powerTransactions'     =>  $powerTransactions,
            'tvTransactions'        =>  $tvTransactions,
            'wallet_transactions'   =>  $wallet_transactions,
        ]);
    }

    public function voucher () {
        $vouchers = \App\Voucher::latest()->get();
        return view('voucher', compact('vouchers'));
    }
}
