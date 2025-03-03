<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class WelcomeUserNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.frontend_url');

        return (new MailMessage)
            ->subject(Lang::get('Your Email Has Been Verified on '.config('app.name')))
            ->greeting(Lang::get('Hello ' . $notifiable->name . ','))
            ->line(Lang::get('Your email has been successfully verified on '.config('app.name') . '! 🎉'))
            ->line(Lang::get('You\'re one step closer to accessing all the features of ' . config('app.name') . '.'))
            ->action('Proceed to Dashboard', $url)
            ->line(__('We’re excited to have you on board!') . __(' If you have any questions, please contact us at ') . '**' . config('mail.from.support') . '**.')
            ->line(Lang::get('No further action is required if you did not initiate this verification or are unsure about this account.'))
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
