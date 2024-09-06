<?php

namespace App\Enums;

enum BusinessType: string
{
    case ADMIN = 'ADMIN';
    case SUPPLIER = 'SUPPLIER';
    case VENDOR = 'VENDOR';
    case CUSTOMER_PHARMACY = 'CUSTOMER_PHARMACY';
    case CUSTOMER_HOSPITAL = 'CUSTOMER_HOSPITAL';

    public static function allowedForRegistration(): array
    {
        return [
            self::SUPPLIER,
            self::VENDOR
        ];
    }

    public function toLowercase(): string
    {
        return strtolower($this->value);
    }
}
