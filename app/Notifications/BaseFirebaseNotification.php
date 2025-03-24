<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseMessagingNotification;

abstract class BaseFirebaseNotification extends Notification implements ShouldQueue
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
    final public function toDatabase(object $notifiable): array
    {
        return [
            'subject' => $this->subject,
            'message' => $this->message,
        ];
    }

    /**
     * Get the Firebase representation of the notification.
     */
    final public function toFirebase(object $notifiable)
    {
    
        // Send the notifications to all the devices
        return $notifiable->deviceTokens->map(fn ($deviceTokens)  =>
                CloudMessage::new()
                ->toToken($deviceTokens->fcm_token) // Use the FCM token from the notifiable
                ->withNotification(FirebaseMessagingNotification::create($this->subject, $this->message))
        )->toArray();

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
