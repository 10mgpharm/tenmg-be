<?php

namespace App\Services;

use App\Enums\MailType;
use App\Mail\Mailer;
use App\Models\Invite;
use App\Models\User;
use App\Services\Contracts\InviteServiceInterface;
use App\Services\Interfaces\IInviteService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Class InviteService
 *
 * Handles the operations related to invites, including storing invites
 * and sending invite links.
 */
class InviteService implements IInviteService
{
    /**
     * Store a new invite in the database.
     *
     * @param array $validated The validated data for creating the invite.
     * @param \App\Models\User $user The user creating the invite.
     * @return \App\Models\Invite|null Returns the created invite model or null on failure.
     */
    public function store(array $validated, User $user): ?Invite
    {
        try {
            // Start a database transaction
            return DB::transaction(function () use ($validated, $user) {
                $invite = $user->invites()->create([
                    ...$validated,
                    'status' => 'INVITED',
                    'business_id' => $user->ownerBusinessType->id,
                    'invite_token' => Str::random(32),
                    'expires_at' => now()->addHours(24),
                ]);

                if ($invite) {
                    $this->sendInviteLink($invite);
                    return $invite; // Return the created invite model
                }

                return null; // Return null on failure
            });
        } catch (Exception $e) {
            // Optionally log the exception here
            throw new Exception('Failed to create invite: ' . $e->getMessage());
        }
    }

    /**
     * Send the invite link to the invited user.
     *
     * @param \App\Models\Invite $invite The invite instance containing details of the invite.
     * @return void
     */
    protected function sendInviteLink(Invite $invite)
    {
        // Send invite email with embedded token
        $invitationUrl = URL::temporarySignedRoute(
            'guest.invite.accept', // Define a named route for accepting the invitation
            now()->addHours(24), // Expiration time
            ['invite_id' => $invite->id, 'token' => $invite->invite_token] // Route parameters
        );;

        $data = [
            'invite' => $invite,
            'invitationUrl' => $invitationUrl,
        ];
        // Use appropriate email method to send out invite (Mailer)
        Mail::to($invite->email)->send(new Mailer(MailType::SEND_INVITATION, $data));
    }
}
