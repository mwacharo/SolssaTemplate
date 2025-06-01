{{-- resources/views/emails/team-invitation.blade.php --}}

@component('mail::message')
# You’ve Been Invited to Join a Team

Hello,

You’ve been invited to join the **{{ $invitation->team->name }}** team on {{ config('app.name') }}.

@component('mail::button', ['url' => route('team-invitations.accept', $invitation->id)])
Accept Invitation
@endcomponent

If you don’t want to accept the invitation, you can safely ignore this email.

Thanks,  
{{ config('app.name') }}
@endcomponent
