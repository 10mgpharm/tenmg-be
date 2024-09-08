<?php

namespace App\Listeners;

use App\Notifications\Auth\WelcomeUserNotification;

class SignupEmailVerifiedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $event->user->notify(new WelcomeUserNotification);
    }
}
