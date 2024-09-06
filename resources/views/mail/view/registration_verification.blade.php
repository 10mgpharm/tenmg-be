<div>
    <h2>Welcome to {{ config('app.name') }}!</h2>

    <p>Thank you for registering with us. To complete your registration and verify your email address, please use the
        One-Time Password (OTP) provided below:
    <p>

    <p><strong>Your OTP:</strong> {{ $otp->code }}</p>

    <p>This OTP is valid for 15 minutes, so please use it promptly. If you did not register for an account, you can
        ignore this email. <b>If you have any questions or need assistance, feel free to reach out to our support team.
    </p>

    <p>Thank you for joining {{ config('app.name') }}!</p>
</div>
