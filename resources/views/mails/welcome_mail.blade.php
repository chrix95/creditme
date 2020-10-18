<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Welcome on Board - {{ env('APP_NAME') }}</title>
</head>
<body>
    <div style="width: 100%; max-width: 100%;margin: 0 auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 16px">
        <h3>Welcome {{ $data['name'] }}</h3>
        <p>
            Welcome on board to {{ env('APP_NAME') }}. We bring you all services under one roof and feel free to reach out to us on any of our customer support lines below: <br> <br>
            <a href="tel:08055117758">08055117758</a> or <a href="tel:07031652192">07031652192</a>
            <br>or email us at: <a href="mailto:{{ env('DEFAULT_EMAIL_ADDRESS') }}">{{ env('DEFAULT_EMAIL_ADDRESS') }}</a>
            <br> <br>
            You login information are: <br>
            <strong>Username:</strong> {{ $data['email'] }} <br>
            <strong>Password:</strong> ******* <br> <br>
            <strong><i>Note: If you forget your password, you can reset your password using the link: <a href="{{ env('APP_URL') }}/password/reset">Reset password</a>.</i></strong>
            <br><br>
            Signed, <br>
            {{ env('APP_NAME') }} Team
        </p>
    </div>
</body>
</html>
