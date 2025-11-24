@php
    /** @var \App\Models\Jobs\JobApplication $application */
    $primary = '#1F85E3';
    $appName = config('app.name', 'Tenmg');
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }} • New Job Application</title>
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
            font-size: 22px;
            color: {{ $primary }};
        }

        .info-list {
            list-style: none;
            padding: 0;
            margin: 24px 0 0;
        }

        .info-list li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(27, 31, 59, 0.08);
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }

        .info-list li strong {
            color: #6c748b;
            min-width: 140px;
        }

        .note {
            margin-top: 24px;
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
    </style>
</head>

<body>
    <table class="wrapper" role="presentation" cellspacing="0" cellpadding="0" width="100%">
        <tr>
            <td align="center">
                <div class="card">
                    <div class="header">
                        <h1>{{ $appName }} • Careers</h1>
                    </div>
                    <div class="body">
                        <h2>New Job Application</h2>
                        <p>
                            A candidate just submitted their details. Review the summary below— their resume is attached
                            for convenience.
                        </p>
                        <ul class="info-list">
                            <li>
                                <strong>Name</strong>
                                <span>{{ $application->first_name }} {{ $application->last_name }}</span>
                            </li>
                            <li>
                                <strong>Email</strong>
                                <span>{{ $application->email }}</span>
                            </li>
                            <li>
                                <strong>Phone</strong>
                                <span>{{ $application->phone }}</span>
                            </li>
                            <li>
                                <strong>Expected Salary</strong>
                                <span>
                                    {{ $application->expected_salary ? number_format($application->expected_salary) : 'N/A' }}
                                    ({{ strtoupper($application->salary_type) }})
                                </span>
                            </li>
                            <li>
                                <strong>Notice Period</strong>
                                <span>{{ $application->notice_period ?? 'N/A' }}</span>
                            </li>
                            <li>
                                <strong>Referral Source</strong>
                                <span>{{ $application->referral_source ?? 'N/A' }}</span>
                            </li>
                        </ul>
                        <p class="note">
                            Resume is attached to this email. If you have issues opening it, download directly from the
                            dashboard.
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
