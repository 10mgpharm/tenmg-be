<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupplierAddBankAccountNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The one-time password (OTP) for bank account verification.
     */
    protected string $otp;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $otp)
    {
        $this->otp = $otp;
    }

    /**
     * Get the notification's delivery channels.
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
        return (new MailMessage)
            ->subject('Bank Account Verification Code')
            ->line('To proceed with adding a bank account to your ' . config('app.name') . ' account, please use the OTP code below:')
            ->line('')
            ->line('**' . $this->otp . '**')
            ->line('')
            ->line('This code is valid for a limited time. Do not share it with anyone.')
            ->line('If you did not initiate this request, please ignore this email.')
            ->salutation('Best Regards, The ' . config('app.name') . ' Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'otp' => $this->otp,
        ];
    }
}
