<?php

namespace App\Enums\Enums;

use Illuminate\Http\Response;

enum MailType: string
{
    case REGISTRATION_VERIFICATION = 'registration_verification';
    case PASSWORD_RESET = 'password_reset';

    /**
     * Get the HTTP status code associated with the mail type.
     *
     * @return int
     */
    public function httpStatusCode(): int
    {
        return match($this) {
            self::REGISTRATION_VERIFICATION => Response::HTTP_CREATED,
            self::PASSWORD_RESET => Response::HTTP_OK,
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
            self::REGISTRATION_VERIFICATION => 'Verify Your Registration',
            self::PASSWORD_RESET => 'Reset Your Password',
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
            self::REGISTRATION_VERIFICATION => 'mail.view.registration_verification',
            self::PASSWORD_RESET => 'mail.password_reset',
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
            self::REGISTRATION_VERIFICATION => 'mail.text.registration_verification',
            self::PASSWORD_RESET => 'mail.text.password_reset',
        };
    }

    /**
     * Get the plain mark view for the mail.
     *
     * @return string
     */
    public function markdown(): string
    {
        return match($this) {
            self::REGISTRATION_VERIFICATION => 'mail.markdown.registration_verification',
            self::PASSWORD_RESET => 'mail.text.password_reset',
        };
    }
}