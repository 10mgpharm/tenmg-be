<?php

namespace App\Notifications\Order;

use App\Notifications\BaseFirebaseNotification;

class NewOrderPaymentNotification extends BaseFirebaseNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(protected string $subject, protected string $message)
    {
        parent::__construct($subject, $message);
    }
}
