@extends('layouts.app')

@section('content')
<div class="container-fluid" id="mainPage">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body" style="padding: 1.25rem;">
                    <div class="info info-hover text-center">
                        <div class="icon icon-primary">
                            <i class="now-ui-icons users_circle-08" style="font-size: 70px;"></i>
                        </div>
                        <h4 class="info-title">{{ Auth::user()->name }}</h4>
                        <p class="description">
                            {{ Auth::user()->email }} <br>
                            {{ Auth::user()->phone }}
                        </p>
                        <fund-wallet passcode="{{ env('VERIFY_HASH_KEY') }}" user="{{ Auth::check() ? Auth::user() : "" }}" paystack="{{ env('MODE') == 1 ? env('PAYSTACK_TEST_PUBLIC_KEY') : env('PAYSTACK_LIVE_PUBLIC_KEY') }}" /> mb-3
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card text-center">
                <h4>Transaction History</h4>
                <div class="card-header">
                    <ul class="nav nav-pills nav-pills-primary justify-content-center mt-4">
                        <li class="nav-item mb-3">
                            <a class="nav-link active" data-toggle="tab" href="#power" role="tab">Electricity</a>
                        </li>
                        <li class="nav-item mb-3">
                            <a class="nav-link" data-toggle="tab" href="#airtime" role="tab">Airtime</a>
                        </li>
                        <li class="nav-item mb-3">
                            <a class="nav-link" data-toggle="tab" href="#tv" role="tab">Cable TV</a>
                        </li>
                        <li class="nav-item mb-3">
                            <a class="nav-link" data-toggle="tab" href="#data" role="tab">Data</a>
                        </li>
                        <li class="nav-item mb-3">
                            <a class="nav-link" data-toggle="tab" href="#wallet" role="tab">Wallet</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body mt-3">
                    <div class="tab-content">
                        <div class="tab-pane active" id="power" role="tabpanel">
                            @include('transactions.power')
                        </div>
                        <div class="tab-pane" id="airtime" role="tabpanel">
                            @include('transactions.airtime')
                        </div>
                        <div class="tab-pane" id="tv" role="tabpanel">
                            @include('transactions.tv')
                        </div>
                        <div class="tab-pane" id="data" role="tabpanel">
                            @include('transactions.data')
                        </div>
                        <div class="tab-pane" id="wallet" role="tabpanel">
                            @include('transactions.wallet')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('javascripts')
    <script>
        
    </script>
@endsection
