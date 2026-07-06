<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 200px;
            height: auto;
        }
        .verification-code {
            background-color: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 3px;
            color: #495057;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 14px;
            color: #6c757d;
            text-align: center;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ asset('images/aleph-one-logo.png') }}" alt="Aleph∞One" class="logo">
        <h1>Email Verification</h1>
    </div>

    <p>Hello {{ $userName }},</p>

    <p>Thank you for registering with AlephâOne. To complete your registration, please enter the following verification code in the application:</p>

    <div class="verification-code">
        {{ $verificationCode }}
    </div>

    <div class="warning">
        <strong>Important:</strong> This verification code will expire in 15 minutes for security reasons.
    </div>

    <p>If you did not create an account with AlephâOne, please ignore this email.</p>

    <p>Best regards,<br>

    The Aleph∞One Team</p>

    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>&copy; {{ date('Y') }} Aleph∞One. All rights reserved.</p>
    </div>
</body>
</html> 