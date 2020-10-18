@extends('layouts.app')
@section('content')
<div class="container-fluid" id="mainPage">
    <voucher passcode="{{ env('VERIFY_HASH_KEY') }}" transactions="{{ json_encode($vouchers) }}" /> 
</div>
@endsection
