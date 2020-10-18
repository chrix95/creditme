@extends('layouts.app')
@section('content')
    <div class="section" id="mainPage">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-12">
                    <div id="sampleCarousel" class="carousel slide carousel-fade" data-ride="carousel">
                        <ol class="carousel-indicators">
                            <li data-target="#sampleCarousel" data-slide-to="0" class="active"></li>
                            <li data-target="#sampleCarousel" data-slide-to="1"></li>
                            <li data-target="#sampleCarousel" data-slide-to="2"></li>
                            <li data-target="#sampleCarousel" data-slide-to="3"></li>
                            <li data-target="#sampleCarousel" data-slide-to="4"></li>
                        </ol>
                        <div class="carousel-inner" role="listbox">
                            <div class="carousel-item active">
                                <img class="d-block" src="{{ asset('ui/assets/img/bg1.jpg') }}" alt="First slide">
                                <div class="carousel-caption d-none d-md-block">
                                </div>
                            </div>
                            <div class="carousel-item">
                                <img class="d-block" src="{{ asset('ui/assets/img/bg3.jpg') }}" alt="Second slide">
                                <div class="carousel-caption d-none d-md-block">
                                </div>
                            </div>
                            <div class="carousel-item">
                                <img class="d-block" src="{{ asset('ui/assets/img/bg4.jpg') }}" alt="Third slide">
                                <div class="carousel-caption d-none d-md-block">
                                </div>
                            </div>
                            <div class="carousel-item">
                                <img class="d-block" src="{{ asset('ui/assets/img/bg2.jpg') }}" alt="Fourth slide">
                                <div class="carousel-caption d-none d-md-block">
                                </div>
                            </div>
                            <div class="carousel-item">
                                <img class="d-block" src="{{ asset('ui/assets/img/bg5.jpg') }}" alt="Fifth slide">
                                <div class="carousel-caption d-none d-md-block">
                                </div>
                            </div>
                        </div>
                        <a class="carousel-control-prev" href="#sampleCarousel" role="button" data-slide="prev">
                            <i class="now-ui-icons arrows-1_minimal-left"></i>
                        </a>
                        <a class="carousel-control-next" href="#sampleCarousel" role="button" data-slide="next">
                            <i class="now-ui-icons arrows-1_minimal-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs justify-content-center" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-toggle="tab" href="#power" role="tab">
                                        <i class="now-ui-icons business_bulb-63"></i> Electricity
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#airtime" role="tab">
                                        <i class="now-ui-icons tech_mobile"></i> Airtime
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#cabletv" role="tab">
                                        <i class="now-ui-icons tech_tv"></i> Cable TV
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#data" role="tab">
                                        <i class="now-ui-icons objects_globe"></i> Data
                                    </a>
                                </li>
                                @if (Auth::check())
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#wallet" role="tab">
                                        <i class="now-ui-icons business_briefcase-24"></i> Wallet
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </div>
                        <div class="card-body">
                            <!-- Tab panes -->
                            <div class="tab-content text-center">
                                @if (Auth::check())
                                <h5 style="margin: 0px auto 10px;">Welcome Back, {{ Auth::user()->name }}</h5>
                                @endif
                                <div class="tab-pane active" id="power" role="tabpanel">
                                    <power-component passcode="{{ env('VERIFY_HASH_KEY') }}" user="{{ Auth::check() ? Auth::id() : "" }}" paystack="{{ env('MODE') == 1 ? env('PAYSTACK_TEST_PUBLIC_KEY') : env('PAYSTACK_LIVE_PUBLIC_KEY') }}" />
                                </div>
                                <div class="tab-pane" id="airtime" role="tabpanel">
                                    <airtime-component passcode="{{ env('VERIFY_HASH_KEY') }}" user="{{ Auth::check() ? Auth::id() : "" }}" paystack="{{ env('MODE') == 1 ? env('PAYSTACK_TEST_PUBLIC_KEY') : env('PAYSTACK_LIVE_PUBLIC_KEY') }}" />
                                </div>
                                <div class="tab-pane" id="cabletv" role="tabpanel">
                                    <tv-component passcode="{{ env('VERIFY_HASH_KEY') }}" user="{{ Auth::check() ? Auth::id() : "" }}" paystack="{{ env('MODE') == 1 ? env('PAYSTACK_TEST_PUBLIC_KEY') : env('PAYSTACK_LIVE_PUBLIC_KEY') }}" />
                                </div>
                                <div class="tab-pane" id="data" role="tabpanel">
                                    <data-component passcode="{{ env('VERIFY_HASH_KEY') }}" user="{{ Auth::check() ? Auth::id() : "" }}" paystack="{{ env('MODE') == 1 ? env('PAYSTACK_TEST_PUBLIC_KEY') : env('PAYSTACK_LIVE_PUBLIC_KEY') }}" />
                                </div>
                                @if (Auth::check())
                                <div class="tab-pane" id="wallet" role="tabpanel">
                                    <wallet-component passcode="{{ env('VERIFY_HASH_KEY') }}" user="{{ Auth::check() ? Auth::user() : "" }}" transactions="{{ json_encode($transactionHistory) }}" paystack="{{ env('MODE') == 1 ? env('PAYSTACK_TEST_PUBLIC_KEY') : env('PAYSTACK_LIVE_PUBLIC_KEY') }}" />
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection