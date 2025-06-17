<?php

namespace App\Notifications;

use App\Models\EcommerceProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GoodsExpirationNotification extends BaseFirebaseNotification
{
    use Queueable;

    protected string $subject = 'Goods Expiration';

    protected string $message = 'Some goods are close to expiration. Please review them.';
    /**
     * Create a new notification instance.
     */
    public function __construct(protected EcommerceProduct $product)
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
            ->line("The following product is near expiration:")
            ->line("**Product:** {$this->product->name} ({$this->product->slug})")
            ->line('Please take appropriate action.')
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
