@extends('layouts.app')
@section('content')
  <div class="page-header clear-filter" filter-color="orange">
    <div class="page-header-image" style="background-image:url({{ asset('ui/assets/img/login.jpg') }})"></div>
    <div class="content">
      <div class="container">
        <div class="col-md-6 ml-auto mr-auto">
          <div class="card card-login card-plain">
            <form class="form" method="POST" action="{{ route('login') }}" autocomplete="off">
              @csrf
              <div class="card-header text-center">
                <h3>Welcome back, Login</h3>
              </div>
              <div class="card-body">
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
                <div class="input-group no-border input-lg">
                  <div class="input-group-prepend">
                    <span class="input-group-text">
                      <i class="now-ui-icons ui-1_lock-circle-open"></i>
                    </span>
                  </div>
                  <input id="password" type="password" placeholder="Password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                  @error('password')
                    <span class="invalid-feedback" role="alert">
                      <strong>{{ $message }}</strong>
                    </span>
                  @enderror
                </div>
              </div>
              <div class="card-footer text-center">
                <button class="btn btn-primary btn-round btn-lg btn-block" type="submit">Login</button>
                <div class="row">
                  <div class="col-md-12">
                    <div class="pull-left">
                      <h6>
                        <a href="{{ route('register') }}" class="link">Don't have an Account?</a>
                      </h6>
                    </div>
                    <div class="pull-right">
                      <h6>
                        <a href="{{ route('password.request') }}" class="link">Forgot password?</a>
                      </h6>
                    </div>
                  </div>
                </div>
                <div class="col-md-12 text-center">
                  <h6>
                    <a href="{{ route('guest.home') }}" class="link">Back to Home</a>
                  </h6>
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
