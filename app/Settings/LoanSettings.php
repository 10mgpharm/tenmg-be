<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class LoanSettings extends Settings
{
    public ?int $lenders_interest;
    public ?int $tenmg_interest;

    public static function group(): string
    {
        return 'general';
    }
}
