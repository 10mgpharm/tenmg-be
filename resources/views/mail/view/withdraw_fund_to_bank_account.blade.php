@component('mail::message')
# Fund Withdrawal Verification Code

Dear {{ $user->name }},  

We received a request to withdraw funds to your linked bank account. To proceed with the withdrawal, please use the OTP code below to verify your identity:

## **{{ $otp }}**

This code is valid for **15 minutes**. Please enter it on the withdrawal confirmation page to complete the transaction.

**Important:**  
- This OTP is for your security. Do not share it with anyone.
- If you did not initiate this withdrawal request, please ignore this email or contact our support team immediately.

Thank you for choosing **{{ config('app.name') }}**.  
We are here to help you!

Best Regards,  
The **{{ config('app.name') }}** Team  
@endcomponent
