<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Mail</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #2A3239;
            padding: 30px;
            text-align: center;
        }
        .header img {
            max-width: 150px;
        }
        .header h1 {
            color: #ffffff;
            margin: 10px 0 0;
            font-size: 22px;
        }
        .body {
            padding: 30px;
            color: #333333;
            line-height: 1.6;
        }
        .body p {
            margin: 0 0 16px;
        }
        .footer {
            background-color: #f4f4f4;
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #999999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>OneWay — Test Email</h1>
        </div>
        <div class="body">
            <p>Hello,</p>
            <p>This is a <strong>test email</strong> sent from the OneWay platform to verify that the mail configuration is working correctly.</p>
            <p>If you received this, your mail setup is working! ✅</p>
            <p>
                Sent at: <strong>{{ now()->format('Y-m-d H:i:s') }}</strong>
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} OneWay. All rights reserved.
        </div>
    </div>
</body>
</html>
