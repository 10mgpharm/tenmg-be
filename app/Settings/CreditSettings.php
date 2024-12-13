<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CreditSettings extends Settings
{
    public ?int $interest_config;
    public ?string $logo;

    public static function group(): string
    {
        return 'general';
    }
}
