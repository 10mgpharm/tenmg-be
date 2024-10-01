<?php

namespace App\Services;

use App\Enums\MailType;
use App\Mail\Mailer;
use App\Models\BusinessUser;
use App\Models\Invite;
use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\InviteServiceInterface;
use App\Services\Interfaces\IInviteService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            'guest.invite.view', // Define a named route for accepting the invitation
            $invite->expires_at,
            ['inviteId' => $invite->id, 'inviteToken' => $invite->invite_token] // Route parameters
        );

        $data = [
            'invite' => $invite,
            'invitationUrl' => $invitationUrl,
        ];
        // Use appropriate email method to send out invite (Mailer)
        Mail::to($invite->email)->send(new Mailer(MailType::SEND_INVITATION, $data));
    }

    /**
     * Retrieve the invitation details for a guest user.
     *
     * This method fetches the invite using the invite token from the query string, then prepares
     * and returns an array of the invite details, including role name, full name, email, and signed URLs 
     * for accepting or rejecting the invitation.
     *
     * @return array The invitation details including role, full name, email, and signed URLs.
     */
    public function view()
    {
        // Retrieve the invite by invite token from the query string
        $invite = Invite::firstWhere('invite_token', request()->query('inviteToken'));

        // Prepare the invitation details including signed URLs for acceptance and rejection
        $data = [
            'role' => $invite->role->name,
            'fullName' => $invite->full_name,
            'email' => $invite->email,
            'acceptUrl' => URL::temporarySignedRoute(
                'guest.invite.accept',
                $invite->expires_at,
                ['inviteId' => $invite->id, 'inviteToken' => $invite->invite_token]
            ),
            'rejectUrl' => URL::temporarySignedRoute(
                'guest.invite.reject',
                $invite->expires_at,
                ['inviteId' => $invite->id, 'inviteToken' => $invite->invite_token]
            )
        ];

        return $data;
    }

    /**
     * Accept an invite and create a new user based on the invite details.
     *
     * @param array $validated The validated data, including password and other form fields.
     * @param Invite $invite The invite model containing the invite details.
     * @return User The newly created user.
     * @throws Exception If the invite acceptance process fails.
     */
    public function accept(array $validated, Invite $invite): User
    {
        try {
            return DB::transaction(function () use ($validated, $invite) {

                // Create user
                $user = User::create([
                    'name' => $invite->full_name,
                    'email' => $invite->email,
                    'email_verified_at' => now(),
                    'password' => Hash::make($validated['password']),
                ]);

                // Resolve role based on the invite and assign it to the user
                $role = $this->resolveRole($invite->role);
                $user->assignRole($role);

                // Create BusinessUser entry to link the user to a business
                BusinessUser::create([
                    'user_id' => $user->id,
                    'business_id' => $invite->business_id,
                    'role_id' => $role->id,
                    'active' => 1,
                ]);

                $user->save();

                // Delete the invite after successful user creation
                $invite->delete();

                return $user->refresh(); // Return the updated user instance
            });
        } catch (Exception $e) {
            // Optionally log the exception here
            throw new Exception('Failed to accept invite: ' . $e->getMessage());
        }
    }

    /**
     * Resolve the appropriate role for the user based on the invite's role.
     *
     * @param Role $role The role assigned in the invite.
     * @return Role The resolved role for the user.
     */
    protected function resolveRole(Role $role): Role
    {
        switch ($role->name) {
            case 'admin':
                return Role::where('name', 'vendor')->first(); // Example of role reassignment
            default:
                return $role;
        }
    }
}
