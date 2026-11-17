<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .email-container {
            width: 100%;
            background-color: #ffffff;
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .email-header {
            text-align: center;
            padding-bottom: 20px;
        }

        .email-header h1 {
            color: #007bff;
            font-size: 32px;
        }

        .email-content {
            padding: 20px;
        }

        .reset-button {
            display: inline-block;
            background-color: #007bff;
            color: #ffffff;
            padding: 15px 25px;
            font-size: 16px;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
        }

        .footer {
            text-align: center;
            font-size: 14px;
            color: #777;
            padding: 20px;
        }

        .footer a {
            color: #36393b;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Password Reset Request</h1>
        </div>

        <div class="email-content">
            <p>Hello,</p>
            <p>We received a request to reset your password. You can reset your password by clicking the button below:
            </p>

            <!-- Reset Password Button -->
            <a href="{{ $actionLink }}" class="reset-button">Reset Your Password</a>

            <p>If you did not request this password reset, no further action is required.</p>

            <p>Thank you, <br> The {{ config('app.name', 'SaleLedger') }} Team</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'SaleLedger') }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
