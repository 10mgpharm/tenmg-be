<?php

namespace App\Enums;

/**
 * Enum for specifying how a discount is applied.
 */
enum DiscountApplicationMethodEnum: string
{
    case COUPON = 'COUPON'; // Applied using a coupon code
    case AUTOMATIC = 'AUTOMATIC'; // Automatically applied without a code
}
