<?php

namespace App\Notifications;

use App\Notifications\BaseFirebaseNotification;

class UserSuspensionNotification extends BaseFirebaseNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(protected string $subject, protected string $message)
    {
        parent::__construct($subject, $message);
    }
}
