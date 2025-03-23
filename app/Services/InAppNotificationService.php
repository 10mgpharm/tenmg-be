<?php

namespace App\Services;

use App\Enums\InAppNotificationType;
use App\Models\User;
use App\Models\NotificationLog;
use App\Notifications\NewMessageNotification;
use App\Notifications\LicenseUploadNotification;
use App\Notifications\LicenseRejectNotification;
use App\Notifications\LicenseAcceptanceNotification;
use App\Notifications\Loan\LoanApprovedNotification;
use App\Notifications\Loan\NewLoanRequestNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

class InAppNotificationService
{
    /**
     * The user(s) for whom the notification operations are being performed.
     */
    protected User|Collection|null $recipients = null;

    /**
     * Set the user for whom the notification operations will be performed.
     *
     * @param  User  $user  The user instance.
     */
    public function forUser(User $user): self
    {
        $this->recipients = $user;
        return $this;
    }

    /**
     * Set the users for whom the notification operations will be performed.
     *
     * @param  Collection  $users  The collection of users.
     */
    public function forUsers(Collection $users): self
    {
        $this->recipients = $users;
        return $this;
    }

    /**
     * Send a notification based on the type.
     *
     * @param  InAppNotificationType  $type  The type of notification.
     * @param  array  $data  Additional data for the notification.
     */
    public function notify(InAppNotificationType $type, array $data = []): self
    {
        // Determine the recipient(s)
        $recipients = $this->recipients ?? request()->user(); // If no recipients are set, use the currently logged-in user
        
        if (!$recipients) {
            throw new \RuntimeException('No recipients found.');
        }

        $role = $data['role'] ?? null;

        // Get the subject and message from the enum
        $subject = $type->subject();
        $message = $type->message($role);

        // Create the appropriate notification instance
        $notification = match ($type) {
            InAppNotificationType::NEW_MESSAGE => new NewMessageNotification($subject, $message),
            InAppNotificationType::LICENSE_UPLOAD => new LicenseUploadNotification($subject, $message),
            InAppNotificationType::ADMIN_LICENSE_UPLOAD => new LicenseUploadNotification($subject, $message),
            InAppNotificationType::LICENSE_REJECTION => new LicenseRejectNotification($subject, $message),
            InAppNotificationType::LICENSE_ACCEPTANCE => new LicenseAcceptanceNotification($subject, $message),
            InAppNotificationType::NEW_LOAN_REQUEST => new NewLoanRequestNotification($subject, $message),
            InAppNotificationType::LOAN_REQUEST_APPROVED => new LoanApprovedNotification($subject, $message),
            default => throw new \InvalidArgumentException("Unsupported notification type: {$type->value}"),
        };

        // Send the notification to the recipient(s)
        if ($recipients instanceof Collection) {
            Notification::send($recipients, $notification);
        } else {
            $recipients->notify($notification);
        }

        return $this;
    }

}
