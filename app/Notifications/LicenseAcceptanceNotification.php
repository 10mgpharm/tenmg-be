<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseMessagingNotification;

class LicenseAcceptanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected string $subject, protected string $message)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'firebase'];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'subject' => $this->subject,
            'message' => $this->message,
        ];
    }

    public function toFirebase(object $notifiable)
    {
        // Check if the FCM token is present
        if (empty($notifiable->fcm_token)) {
            logs()->warning("FCM token not found for user. User ID: {$notifiable->id}, Name: {$notifiable->name}, Email: {$notifiable->email}");
            return null; // Return null to indicate no message was created
        }

        // Create the CloudMessage object
        return CloudMessage::new()
            ->toToken($notifiable->fcm_token) // Use the FCM token from the notifiable
            ->withNotification(FirebaseMessagingNotification::create($this->subject, $this->message));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
