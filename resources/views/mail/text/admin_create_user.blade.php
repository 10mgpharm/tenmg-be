# Welcome to {{ config('app.name') }}, {{ $user->name }}!

Your account has been successfully created on {{ config('app.name') }}.

You can log in using the following details:

**Email:** {{ $user->email }}  
**Password:** {{ $password }}

Use the link below to access your account:

{{ route('auth.signin') }}

Please make sure to change your password after logging in for security purposes.

If you did not expect this account to be created, no further action is required.

Best Regards,  
The {{ config('app.name') }} Team
