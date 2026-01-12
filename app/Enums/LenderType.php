<?php

namespace App\Enums;

enum LenderType: string
{
    case INDIVIDUAL = 'individual';
    case BUSINESS = 'business';

    public function toLowercase(): string
    {
        return strtolower($this->value);
    }
}
