# Welcome to {{ config('app.name') }}!

You have been invited to join {{ $invite->business->name }} on {{ config('app.name') }}

Use the link below to accept the invitation:

{!! $invitationUrl !!}


This invitation will expire in {{ $invite->expires_at->diffForHumans() }}.

If you did not expect this invitation, no further action is required.

Best Regards,
The {{ config('app.name') }} Team
