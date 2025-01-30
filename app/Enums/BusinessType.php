<?php

namespace App\Enums;

enum BusinessType: string
{
    case ADMIN = 'ADMIN';
    case SUPPLIER = 'SUPPLIER';
    case VENDOR = 'VENDOR';
    case CUSTOMER_PHARMACY = 'CUSTOMER_PHARMACY';
    case LENDER = 'LENDER';

    public static function allowedForRegistration(): array
    {
        return [
            self::SUPPLIER,
            self::VENDOR,
            self::CUSTOMER_PHARMACY,
            self::LENDER
        ];
    }

    public function toLowercase(): string
    {
        return strtolower($this->value);
    }
}
