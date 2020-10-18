@extends('layouts.app')
@section('content')
  <div class="contact-content">
    <div class="container">
        <div class="row">
            <div class="col-md-5 ml-auto mr-auto">
                <h2 class="title">Send us a message</h2>
                <p class="description">You can contact us with anything related to our Products. We'll get in touch with you as soon as possible.<br><br>
                </p>
                <form method="post" action="{{ route('contact.submit') }}">
                    @csrf
                    @method('POST')
                    @if (Session::has('success'))
                        <div class="alert alert-success text-center" role="alert" style="border-radius: 0px">
                            <strong>{{ Session::get('success') }}</strong>
                        </div>
                    @endif
                    @if (Session::has('error'))
                        <div class="alert alert-warning text-center" role="alert" style="border-radius: 0px">
                            <strong>{{ Session::get('error') }}</strong>
                        </div>
                    @endif
                    <label>Your name</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="now-ui-icons users_circle-08"></i></span>
                        </div>
                        <input type="text" name="name" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" value="{{old('name')}}" placeholder="Your Name..." aria-label="Your Name..." autocomplete="name">
                        @if ($errors->has('name'))
                            <span class="invalid-feedback" role="alert">
                                <strong class="text-danger">{{ $errors->first('name') }}</strong>
                            </span>
                        @endif
                    </div>
                    <label>Email address</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="now-ui-icons ui-1_email-85"></i></span>
                        </div>
                        <input type="email" name="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" placeholder="Email Here..." aria-label="Email Here..." autocomplete="email" value="{{old('email')}}">
                        @if ($errors->has('email'))
                            <span class="invalid-feedback" role="alert">
                                <strong class="text-danger">{{ $errors->first('email') }}</strong>
                            </span>
                        @endif
                    </div>
                    <label>Phone</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="now-ui-icons tech_mobile"></i></span>
                        </div>
                        <input type="text" name="phone" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }}" placeholder="Number Here..." autocomplete="number" value="{{old('phone')}}">
                        @if ($errors->has('phone'))
                            <span class="invalid-feedback" role="alert">
                                <strong class="text-danger">{{ $errors->first('phone') }}</strong>
                            </span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Your message</label>
                        <textarea name="message" class="form-control{{ $errors->has('message') ? ' is-invalid' : '' }}" id="message" rows="6">{{old('message')}}</textarea>
                        @if ($errors->has('message'))
                            <span class="invalid-feedback" role="alert">
                                <strong class="text-danger">{{ $errors->first('message') }}</strong>
                            </span>
                        @endif
                    </div>
                    <div class="submit text-center">
                        <input type="submit" class="btn btn-primary btn-raised btn-round" value="Send message">
                    </div>
                </form>
            </div>
            <div class="col-md-5 ml-auto mr-auto">
                <div class="info info-horizontal mt-5">
                    <div class="description">
                        <h4 class="info-title">Find us at the office</h4>
                        <p class="description">
                            <i class="now-ui-icons location_pin"></i> {{ env('DEFAULT_ADDRESS') }}
                        </p>
                    </div>
                </div>
                <div class="info info-horizontal">
                    <div class="description">
                        <h4 class="info-title">Give us a ring</h4>
                        <p class="description">
                            <i class="now-ui-icons tech_mobile"></i> (234)805 511 7758, (234)703 165 2192 <br>
                            Mon - Fri, 8:00-22:00
                        </p>
                    </div>
                </div>
                <div class="info info-horizontal">
                    <div class="icon icon-primary">
                        
                    </div>
                    <div class="description">
                        <h4 class="info-title">Chat us on whatsapp</h4>
                        <a class="nav-link" target="_blank" href="https://api.whatsapp.com/send?phone=2348058930195&text=How%20may%20we%20help%20you%20today%20">
                           <p class="description">
                               <i class="fa fa-whatsapp"></i> +2348058930195
                           </p>
                        </a>
                    </div>
                </div>
            </div>
       </div>
    </div>
  </div>
@endsection