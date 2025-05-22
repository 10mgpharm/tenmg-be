<?php

namespace App\Services;

use App\Enums\OtpType;
use App\Models\User;
use App\Services\Interfaces\IAccountService;
use Illuminate\Support\Facades\DB;

class AccountService implements IAccountService
{
    public function __construct(private AttachmentService $attachmentService, private OtpService $otpService) {}

    /**
     * Update the user's profile with the provided data.
     */
    public function updateProfile(User $user, array $data): User
    {
        try {
            return DB::transaction(function () use ($user, $data) {
                // Check if profile picture is being updated.
                if (isset($data['profilePicture'])) {
                    $created = $this->attachmentService->saveNewUpload(
                        $data['profilePicture'],
                        $user->id,
                        User::class
                    );
                    $data['avatar_id'] = $created?->id ?: $user->avatar_id;
                    unset($data['profilePicture']); // Remove from data to avoid error during update.
                }

                // Check if email has changed.
                if (isset($data['email']) && $data['email'] !== $user->email) {
                    // Send OTP to the new email.
                    $this->otpService->forUser($user)
                        ->generate(OtpType::CHANGE_EMAIL_VERIFICATION)
                        ->sendMail(OtpType::CHANGE_EMAIL_VERIFICATION, $data['email']);

                    $data['email_verified_at'] = null;
                }

                // Update the user profile.
                $isUpdate = $user->update($data);

                if ($isUpdate) {
                    // Log the update event.
                    AuditLogService::log(
                        target: $user, // The user is the target (it is being updated)
                        event: 'update.user', // The event name
                        action: "Updated profile",
                        description: "{$user->name} updated their profile information",
                        crud_type: 'UPDATE',
                        properties: $data,
                    );
                }

                return $user->fresh(); // Return the updated user model.
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
