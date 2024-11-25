@component('mail::message')
# Welcome to {{ config('app.name') }}, {{ $user->name }}!

Your account has been successfully created on {{ config('app.name') }}.

You can log in using the following details:

**Email:** {{ $user->email }}  
**Password:** {{ $password }}

@component('mail::button', ['url' => config('app.frontend_url') . '/auth/signin' ])
Access Your Account
@endcomponent

Please make sure to change your password after logging in for security purposes.

If you did not expect this account to be created, no further action is required.

Best Regards,<br>
The {{ config('app.name') }} Team
@endcomponent
