<?php

namespace App\Enums;

use Illuminate\Http\Response;

enum MailType: string
{
    case SEND_INVITATION = 'send_invitation';

    /**
     * Get the HTTP status code associated with the mail type.
     *
     * @return int
     */
    public function httpStatusCode(): int
    {
        return match($this) {
            self::SEND_INVITATION => Response::HTTP_CREATED,
        };
    }

    /**
     * Get the subject of the mail.
     *
     * @return string
     */
    public function subject(): string
    {
        return match($this) {
            self::SEND_INVITATION => 'You have been invited',
        };
    }

    /**
     * Get the Blade view for the mail.
     *
     * @return string
     */
    public function view(): string
    {
        return match($this) {
            self::SEND_INVITATION => 'mail.view.send_invitation',
        };
    }

    /**
     * Get the plain text view for the mail.
     *
     * @return string
     */
    public function text(): string
    {
        return match($this) {
            self::SEND_INVITATION => 'mail.text.send_invitation',
        };
    }

}