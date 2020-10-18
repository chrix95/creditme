<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="ui/assets/img/logo.png" type="image/x-icon" />

    <title>{{ config('app.name', 'Laravel') }}</title>
    @include('layouts.css')
    @if (Request::is('register'))
    <style>
        .login-page .card-login {
            max-width: 100%;
        }
        .page-header>.content {
            margin-top: 5%;
        }
    </style>
    @endif
</head>
<body class="login-page sidebar-collapse">
    <div id="app">
       @if (!Request::is('login') && !Request::is('register') && !Request::is('password/reset') && !Request::is('password/reset/*'))
        @include('_partials.navigator')
       @endif
        <div class="main">
            @yield('content')
            @include('modals.index')
            @if(!Request::is('login') && !Request::is('register') && !Request::is('password/reset') && !Request::is('password/reset/*'))
            @include('_partials.footer')
            @endif
        </div>
    </div>
    @include('layouts.js')
    @yield('javascripts')
</body>
</html>
