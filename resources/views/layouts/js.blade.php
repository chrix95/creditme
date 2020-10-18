<!--   Core JS Files   -->
<script src="{{ asset('ui/assets/js/core/jquery.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('ui/assets/js/core/popper.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('ui/assets/js/core/bootstrap.min.js') }}" type="text/javascript"></script>
<!--  Plugin for Switches, full documentation here: http://www.jque.re/plugins/version3/bootstrap.switch/ -->
<script src="{{ asset('ui/assets/js/plugins/bootstrap-switch.js') }}"></script>
<!--  Plugin for the Sliders, full documentation here: http://refreshless.com/nouislider/ -->
<script src="{{ asset('ui/assets/js/plugins/nouislider.min.js') }}" type="text/javascript"></script>
<!--  Plugin for the DatePicker, full documentation here: https://github.com/uxsolutions/bootstrap-datepicker -->
<script src="{{ asset('ui/assets/js/plugins/bootstrap-datepicker.js') }}" type="text/javascript"></script>
<!-- Control Center for Now Ui Kit: parallax effects, scripts for the example pages etc -->
<script src="{{ asset('ui/assets/js/now-ui-kit.js?v=1.3.0') }}" type="text/javascript"></script>
@if(env('PAYMENT_MODE') == 1)
<script src="https://js.paystack.co/v1/inline.js"></script>
@endif
@if(env('PAYMENT_MODE') == 2)
<script src="https://api.ravepay.co/flwv3-pug/getpaidx/api/flwpbf-inline.js"></script>
@endif