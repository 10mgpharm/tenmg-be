<?php

namespace App\Notifications;

use App\Models\EcommerceProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShippingListUpdateNotification extends BaseFirebaseNotification
{
    use Queueable;

    protected string $subject = 'Shopping List Updated';

    protected string $message = 'A product has been added to the shopping list.';
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
            ->line("A product has been added to the shopping list.")
            ->line("**Product:** {$this->product->name} ({$this->product->slug})")
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
