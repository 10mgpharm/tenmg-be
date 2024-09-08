<?php

namespace App\Enums;

enum BusinessType: string
{
    case ADMIN = 'ADMIN';
    case SUPPLIER = 'SUPPLIER';
    case VENDOR = 'VENDOR';
    case CUSTOMER_PHARMACY = 'CUSTOMER_PHARMACY';

    public static function allowedForRegistration(): array
    {
        return [
            self::SUPPLIER,
            self::VENDOR,
            self::CUSTOMER_PHARMACY,
        ];
    }

    public function toLowercase(): string
    {
        return strtolower($this->value);
    }
}
