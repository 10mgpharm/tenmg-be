<?php

namespace App\Enums;

/**
 * Enum for defining the type of discount.
 */
enum DiscountTypeEnum: string
{
    case PERCENTAGE = 'PERCENTAGE'; // Discount as a percentage of the total amount
    case FIXED = 'FIXED'; // Fixed amount discount
}
