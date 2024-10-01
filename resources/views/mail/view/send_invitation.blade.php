@component('mail::message')
# Welcome to {{ config('app.name') }}!

You have been invited to join {{ $invite->business->name }} on {{ config('app.name') }}

Use the link below to accept the invitation:

@component('mail::button', ['url' => $invitationUrl])
Accept Invitation
@endcomponent

This invitation will expire in 24 hours.

If you did not expect this invitation, no further action is required.

Best Regards,<br>
The {{ config('app.name') }} Team
@endcomponent

