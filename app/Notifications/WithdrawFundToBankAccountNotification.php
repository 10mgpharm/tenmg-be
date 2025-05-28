<?php

namespace App\Notifications;

use App\Models\Otp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawFundToBankAccountNotification extends Notification implements ShouldQueue
{
    use Queueable;
    /**
     * The one-time password (OTP) for fund withdrawal verification.
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
        $firstName = $notifiable->name ? explode(' ', trim($notifiable->name))[0] : '';

        return (new MailMessage)
            ->subject('Fund Withdrawal Verification Code')
            ->greeting("Dear {$firstName},")
            ->line('We received a request to withdraw funds to your linked bank account.')
            ->line('To proceed with the withdrawal, please use the OTP code below to verify your identity:')
            ->line('')
            ->line("**{$this->otp}**") 
            ->line('')
            ->line('This code is valid for 15 minutes. Please enter it on the withdrawal confirmation page to complete the transaction.')
            ->line('')
            ->line('**Important:**')
            ->line('- This OTP is for your security. Do not share it with anyone.')
            ->line('- If you did not initiate this withdrawal request, please ignore this email or contact our support team immediately.')
            ->line('')
            ->line('Thank you for choosing ' . config('app.name') . '. We are here to help you!')
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
