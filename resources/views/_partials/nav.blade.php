<!-- Navbar -->
<nav class="navbar sticky-top navbar-expand-lg main-menu">
  <div class="container">
    <div class="navbar-translate">
      <a class="navbar-brand" href="{{ route('guest.home') }}">
        <img src="ui/assets/img/logo.png" alt="Cardcom Logo" class="img img-responsive center-block" width="80%">
      </a>
      <button class="navbar-toggler navbar-toggler" type="button" data-toggle="collapse" data-target="#navigation" aria-controls="navigation-index" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-bar top-bar"></span>
        <span class="navbar-toggler-bar middle-bar"></span>
        <span class="navbar-toggler-bar bottom-bar"></span>
      </button>
    </div>
    <div class="collapse navbar-collapse justify-content-end" id="navigation">
      <ul class="navbar-nav">
        @if (Auth::check())
        @if (!Request::is('/'))
        <li class="nav-item">
          <a class="nav-link border-bt" href="{{ route('guest.home') }}" style="cursor: pointer">
            Purchase
          </a>
        </li>
        @endif
        @if (Auth::user()->role_id === 1)
        <li class="nav-item">
          <a class="nav-link border-bt" href="{{ route('voucher') }}" style="cursor: pointer">
            Voucher
          </a>
        </li>
        @endif
        <li class="nav-item">
          <a class="nav-link border-bt" href="{{ route('profile') }}" style="cursor: pointer">
            Profile
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link border-bt" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="cursor: pointer">
            Logout
          </a>
          <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
          </form>
        </li>
        @else
        <li class="nav-item">
          <a class="nav-link border-bt" href="{{ route('login') }}" style="cursor: pointer">
            Login
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link border-bt" href="{{ route('register') }}" style="cursor: pointer">
            Sign Up
          </a>
        </li>
        @endif
        <li class="nav-item">
          <a class="nav-link" href="{{ route('contact') }}">
            <p>Contact</p>
          </a>
        </li>
        <div class="side_nav_addon">
          <li class="nav-item social">
            <a class="nav-link" href="https://facebook.com/cardcomservices/" target="_blank">
              <i class="fa fa-facebook"></i>
            </a>
          </li>
          <li class="nav-item social">
            <a class="nav-link" href="https://www.instagram.com/cardcomm/" target="_blank">
              <i class="fa fa-instagram"></i>
            </a>
          </li>
          <li class="nav-item social">
            <a class="nav-link" href="https://www.youtube.com/channel/UC4gj0uv5oNQURKHcACxlGvQ?" target="_blank">
              <i class="fa fa-youtube"></i>
            </a>
          </li>
          <li class="nav-item social">
            <a class="nav-link" href="https://twitter.com/CardcomL" target="_blank">
              <i class="fa fa-twitter"></i>
            </a>
          </li>
        </div>
      </ul>
    </div>
  </div>
</nav>
<!-- End Navbar -->