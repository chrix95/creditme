@if(!Request::is('login') && !Request::is('register') && !Request::is('password/reset') && !Request::is('password/reset/*'))
<footer class="fixed-bottom footer footer-default">
@else
<footer class="fixed-bottom footer">
@endif
  <div class="container">
    <div class="row">
      <div class="col-md-8 col-sm-12">
        <nav>
          <ul>
            <li>
              <a href="{{ route('about') }}">
                About us
              </a>
            </li>
            <li>
              <a href="{{ route('terms') }}">
                T&amp;C
              </a>
            </li>
            <li>
              <a href="{{ route('services') }}">
                Our Services
              </a>
            </li>
          </ul>
        </nav>
      </div>
      <div class="col-md-4 col-sm-12">
        <div class="copyright" id="copyright">
          &copy;
          {{ date('Y') }}. Cardcom Services. Created by <a href="https://devchris.com.ng" target="_blank" style="color: #000">Devchris</a>.
        </div>
      </div>
    </div>
  </div>
</footer>