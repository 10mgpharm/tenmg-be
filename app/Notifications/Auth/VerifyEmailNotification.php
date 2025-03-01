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

        return (new MailMessage)
            ->subject(Lang::get('Verify Your Account on ' . config('app.name')))
            ->greeting(Lang::get('Hello ' . $notifiable->name . ','))
            ->line(Lang::get('Welcome to ' . config('app.name') . '!'))
            ->line(Lang::get('To complete your registration and verify your account, use the code below:'))
            ->line(__('**Verification Code: ') . $this->code . '**')
            ->line(__('This code will expire in **15 minutes.**'))
            ->line(__('We’re excited to have you on board as a ') . '**' . ucwords($type) . '**' . __(' and look forward to working with you. If you have any questions, please contact us at ') . '**' . config('mail.from.support') . '**.')
            ->line(__('No action is required if you did not request an account or are unsure about this email.'))
            ->line('')
            ->line('Best Regards,')
            ->salutation(Lang::get('The '.  config('app.name') . ' Team'));
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
