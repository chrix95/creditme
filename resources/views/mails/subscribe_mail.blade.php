<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Subscription to newsletter - {{ env('APP_NAME') }}</title>
</head>
<body>
    <div style="width: 100%; max-width: 100%;margin: 0 auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 16px">
        <h3>Hi admin,</h3>
        <p>
            Kindly add the following email user to our mailing / subscription list: <br><br>
            <strong>Fullname:</strong> {{ $data['name'] }} <br>
            <strong>Email:</strong> {{ $data['email'] }} <br>
            <strong>Subscription Type:</strong> {{ $data['type'] }} <br>
            <br><br>
            Signed, <br>
            {{ env('APP_NAME') }} Team
        </p>
    </div>
</body>
</html>
