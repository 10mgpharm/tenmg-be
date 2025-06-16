<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserAddedNotification extends BaseFirebaseNotification
{
    use Queueable;

    protected string $subject = 'New User Added';

    protected string $message = 'A new user has been added.';
    /**
     * Create a new notification instance.
     */
    public function __construct(protected User $user)
    {
        parent::__construct($this->subject, $this->message);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return array_merge(parent::via($notifiable), ['mail']);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->greeting("Hello {$notifiable->name},")
            ->line("A new user has been added to the system.")
            ->line("**User Name:** {$this->user->name}")
            ->salutation('Best Regards, The ' . config('app.name') . ' Team');
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
