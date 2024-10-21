<?php

namespace App\Enums;

use Illuminate\Http\Response;

enum MailType: string
{
    case SEND_INVITATION = 'send_invitation';
    case ADMIN_CREATE_USER = 'admin_create_user';

    /**
     * Get the HTTP status code associated with the mail type.
     */
    public function httpStatusCode(): int
    {
        return match ($this) {
            self::SEND_INVITATION => Response::HTTP_CREATED,
            self::ADMIN_CREATE_USER => Response::HTTP_CREATED,
        };
    }

    /**
     * Get the subject of the mail.
     */
    public function subject(): string
    {
        return match ($this) {
            self::SEND_INVITATION => 'You have been invited',
            self::ADMIN_CREATE_USER => 'An account has been created for you',
        };
    }

    /**
     * Get the Blade view for the mail.
     */
    public function view(): string
    {
        return match ($this) {
            self::SEND_INVITATION => 'mail.view.send_invitation',
            self::ADMIN_CREATE_USER => 'mail.view.admin_create_user',
        };
    }

    /**
     * Get the plain text view for the mail.
     */
    public function text(): string
    {
        return match($this) {
            self::SEND_INVITATION => 'mail.text.send_invitation',
            self::ADMIN_CREATE_USER => 'mail.text.admin_create_user',
        };
    }
}
