<h4>New message {{ env('APP_NAME') }}</h4>
<p>Sender: {{$data->name}}</p>
<p>E-Mail: {{$data->email}}</p>
<p>Phone: {{$data->phone}}</p>
<p>Message:</p>
{{$data->message}}
<p>
    &copy; {{ env('APP_NAME') }}. {{date('Y')}}
</p>