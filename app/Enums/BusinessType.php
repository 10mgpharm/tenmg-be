<?php

namespace App\Enums;

enum BusinessType: string
{
    case ADMIN = 'ADMIN';
    case SUPPLIER = 'SUPPLIER';
    case VENDOR = 'VENDOR';
    case CUSTOMER_PHARMACY = 'CUSTOMER_PHARMACY';
    case CUSTOMER_HOSPITAL = 'CUSTOMER_HOSPITAL';
}

enum BusinessStatus: string
{
    case PENDING_VERIFICATION = 'PENDING_VERIFICATION';
    case VERIFIED = 'VERIFIED';
    case SUSPENDED = 'SUSPENDED';
    case BANNED = 'BANNED';
}
