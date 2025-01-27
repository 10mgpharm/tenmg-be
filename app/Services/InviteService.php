<?php

namespace App\Services;

use App\Enums\MailType;
use App\Mail\Mailer;
use App\Models\BusinessUser;
use App\Models\Invite;
use App\Models\Role;
use App\Models\User;
use App\Services\Interfaces\IInviteService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
     * @param  array  $validated  The validated data for creating the invite.
     * @param  \App\Models\User  $user  The user creating the invite.
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
                    'business_id' =>  $user->ownerBusinessType?->id ?: $user->businesses()->firstWhere('user_id', $user->id)?->id,
                    'invite_token' => Str::random(32),
                    'expires_at' => now()->addDays(14),
                ]);

                if ($invite) {
                    $this->sendInviteLink($invite);

                    return $invite; // Return the created invite model
                }

                return null; // Return null on failure
            });
        } catch (Exception $e) {
            throw new Exception('Failed to create invite: '.$e->getMessage());
        }
    }

    /**
     * Send the invite link to the invited user.
     *
     * @param  \App\Models\Invite  $invite  The invite instance containing details of the invite.
     * @return void
     */
    protected function sendInviteLink(Invite $invite)
    {
       // Construct invite URL without signing
        $frontendBaseUrl = config('app.frontend_url') . '/auth/invite';

        // Add query parameters to the URL
        $queryParams = urldecode(http_build_query([
            'inviteId' => $invite->id,
            'inviteToken' => $invite->invite_token,
        ]));

        $invitationUrl = $frontendBaseUrl . '?' . $queryParams;

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
            'role' => strtoupper($invite->role->name),
            'fullName' => $invite->full_name,
            'email' => $invite->email,
            'businessName' => $invite->business->name,
            'acceptUrl' => route('auth.invite.accept', [
                'inviteId' => $invite->id,
                'inviteToken' => $invite->invite_token,
            ]),
        ];

        return $data;
    }

    /**
     * Accept an invite and create a new user based on the invite details.
     *
     * @param  array  $validated  The validated data, including password and other form fields.
     * @param  Invite  $invite  The invite model containing the invite details.
     * @return User The newly created user.
     *
     * @throws Exception If the invite acceptance process fails.
     */
    public function accept(array $validated, Invite $invite): User
    {
        try {
            return DB::transaction(function () use ($validated, $invite) {

                if (User::firstWhere('email', $invite->email)) {
                    throw ValidationException::withMessages([
                        'email' => [__('The selected email address already exist.')],
                    ]);
                }

                // Create user
                $user = User::create([
                    'name' => $invite->full_name,
                    'email' => $invite->email,
                    'email_verified_at' => now(),
                    'password' => Hash::make($validated['password']),
                ]);

                // Resolve role based on the invite and assign it to the user
                $user->assignRole($invite->role->name);

                // Create BusinessUser entry to link the user to a business
                BusinessUser::create([
                    'user_id' => $user->id,
                    'business_id' => $invite->business_id,
                    'role_id' => $invite->role_id,
                    'active' => 1,
                ]);

                $user->save();

                // Delete the invite after successful user creation
                $invite->update(['status' => 'ACCEPTED']);

                return $user->refresh(); // Return the updated user instance
            });
        } catch (HttpException $e) {
            if ($e->getStatusCode() === 500) {
                throw new Exception('Failed to accept invite: '.$e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Reject an invite and update its status to REJECTED.
     *
     * This method performs the status update within a database transaction
     * to ensure data integrity. If the update fails, an exception is thrown.
     *
     * @param  Invite  $invite  The invite instance to be rejected.
     * @return bool Returns true if the invite was successfully rejected;
     *              otherwise, it will throw an exception.
     *
     * @throws Exception If the invite status update fails.
     */
    public function reject(Invite $invite): bool
    {
        try {
            return DB::transaction(function () use ($invite) {
                // Update the invite status to rejected
                $invite->update(['status' => 'REJECTED']);

                return true;
            });
        } catch (Exception $e) {
            throw new Exception('Failed to reject invite: '.$e->getMessage());
        }
    }

    /*
    * Delete an invite from the database.
    */
    public function delete(Invite $invite): bool
    {
        try {
            return DB::transaction(function () use ($invite) {
                return $invite->delete();
            });
        } catch (Exception $e) {
            throw new Exception('Failed to delete invite: '.$e->getMessage());
        }
    }
}
