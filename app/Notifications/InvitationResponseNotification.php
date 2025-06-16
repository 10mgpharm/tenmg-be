<?php

namespace App\Notifications;

use App\Models\Invite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationResponseNotification extends BaseFirebaseNotification
{
    use Queueable;

    protected string $subject = 'Invitation Response';

    protected string $message = 'The invitation sent has been rejected or accepted.';
    /**
     * Create a new notification instance.
     */
    public function __construct(protected Invite $invite)
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
            ->line("An invited member has responded to their invitation.")
            ->line("**{$this->invite->full_name}** has **{$this->invite->status}** the invitation.")
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
