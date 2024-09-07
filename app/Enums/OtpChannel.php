<?php

namespace App\Enums;

enum OtpChannel: string
{
    case EMAIL = 'EMAIL';
    case SMS = 'SMS';
    case AUTH_DEVICE = 'AUTH_DEVICE';

    public function toLowercase(): string
    {
        return strtolower($this->value);
    }
}
