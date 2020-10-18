@extends('layouts.app')
@section('content')
  <div class="page-header clear-filter" filter-color="orange">
    <div class="page-header-image" style="background-image:url({{ asset('ui/assets/img/login.jpg') }})"></div>
    <div class="content">
      <div class="container">
        <div class="col-md-6 ml-auto mr-auto">
          <div class="card card-login card-plain">
            <form class="form" method="POST" action="{{ route('password.email') }}" autocomplete="off">
              @csrf
              <div class="card-header text-center">
                <h3>Oops! Reset password</h3>
              </div>
              <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif
                <div class="input-group no-border input-lg">
                  <div class="input-group-prepend">
                    <span class="input-group-text">
                      <i class="now-ui-icons ui-1_email-85"></i>
                    </span>
                  </div>
                  <input id="email" type="email" placeholder="Email Address" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus>
                  @error('email')
                    <span class="invalid-feedback" role="alert">
                      <strong>{{ $message }}</strong>
                    </span>
                  @enderror
                </div>
              </div>
              <div class="card-footer text-center">
                <button class="btn btn-primary btn-round btn-lg btn-block" type="submit">Send Password Reset Link</button>
                <div class="row">
                  <div class="col-md-12">
                    <div class="pull-left">
                      <h6>
                        <a href="{{ route('login') }}" class="link">Already have an account?</a>
                      </h6>
                    </div>
                    <div class="pull-right">
                      <h6>
                        <a href="{{ route('guest.home') }}" class="link">Back to Home</a>
                      </h6>
                    </div>
                  </div>
                </div>
            </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    @include('_partials.footer')
  </div>
@endsection
