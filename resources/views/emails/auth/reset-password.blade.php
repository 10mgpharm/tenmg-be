@php
    $primary = '#1F85E3';
    $appName = config('app.name');
@endphp
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ config('app.name') }} • Password Reset</title>
        <style>
            body {
                margin: 0;
                padding: 0;
                font-family: "Segoe UI", Arial, sans-serif;
                background: #f5f7fb;
                color: #1b1f3b;
            }
            .wrapper {
                width: 100%;
                padding: 32px 12px;
            }
            .card {
                max-width: 540px;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 12px 30px rgba(31, 133, 227, 0.12);
            }
            .header {
                background: {{ $primary }};
                color: #ffffff;
                padding: 28px 40px;
            }
            .logo {
                display: inline-block;
                margin-bottom: 8px;
            }
            .logo img {
                max-width: 140px;
                height: auto;
                display: block;
            }
            .header h1 {
                margin: 0;
                font-size: 20px;
                letter-spacing: 0.4px;
            }
            .body {
                padding: 32px 40px;
                line-height: 1.6;
            }
            .body h2 {
                margin: 0 0 12px;
                font-size: 24px;
                color: {{ $primary }};
            }
            .code-box {
                margin: 28px 0;
                padding: 18px;
                border: 1px dashed rgba(31, 133, 227, 0.35);
                border-radius: 12px;
                text-align: center;
                font-size: 24px;
                letter-spacing: 6px;
                font-weight: 700;
                color: {{ $primary }};
                background: rgba(31, 133, 227, 0.04);
            }
            .note {
                font-size: 13px;
                color: #6c748b;
            }
            .footer {
                background: #f1f5fb;
                padding: 20px 40px;
                font-size: 12px;
                color: #6c748b;
                text-align: center;
            }
            a {
                color: {{ $primary }};
            }
        </style>
    </head>
    <body>
        <table class="wrapper" role="presentation" cellspacing="0" cellpadding="0" width="100%">
            <tr>
                <td align="center">
                    <div class="card">
                        <div class="header">
                            <span class="logo">
                                <img src="https://cdn.prod.website-files.com/6890140650385aae40925df0/6897ae0336919faea4cc1088_Blue%20Logo.svg" alt="{{ $appName }} logo">
                            </span>
                            <h1>{{ $appName }} • Password Reset</h1>
                        </div>
                        <div class="body">
                            <h2>Hello {{ $firstName }},</h2>
                            <p>
                                We received a request to reset the password on your {{ $appName }} account.
                                Enter the code below to continue.
                            </p>
                            <div class="code-box">
                                {{ $code }}
                            </div>
                            <p class="note">
                                The code expires in 15 minutes. If you didn’t ask to reset your password, you can ignore this email and your account will remain secure.
                            </p>
                        </div>
                        <div class="footer">
                            © {{ date('Y') }} {{ $appName }}. All rights reserved.
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </body>
</html>


