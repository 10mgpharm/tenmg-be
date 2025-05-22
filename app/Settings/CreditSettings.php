<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CreditSettings extends Settings
{
    public ?int $interest_config;
    public ?int $tenmg_ecommerce_commission_percent;
    public ?string $logo;

    public static function group(): string
    {
        return 'general';
    }
}
