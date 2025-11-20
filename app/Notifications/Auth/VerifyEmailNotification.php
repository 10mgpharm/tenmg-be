<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $code) {}

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $type = $notifiable->type ?? $notifiable->getRoleNames()->first();

        if ($type == 'customer') {
            $type = 'Healthcare Provider';
        }

        $firstName = $notifiable->name ? explode(' ', trim($notifiable->name))[0] : 'there';
        $supportEmail = config('mail.from.support', config('mail.from.address'));

        return (new MailMessage)
            ->subject(Lang::get('Verify Your Account on '.config('app.name')))
            ->view('emails.auth.verify-email', [
                'firstName' => $firstName,
                'code' => $this->code,
                'roleLabel' => ucwords($type),
                'supportEmail' => $supportEmail,
            ]);
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
