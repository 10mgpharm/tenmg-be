<?php

namespace App\Notifications\Loan;

use App\Notifications\BaseFirebaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseMessagingNotification;


class LoanApprovedNotification extends  BaseFirebaseNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(protected string $subject, protected string $message)
    {
        parent::__construct($subject, $message);
    }
}
