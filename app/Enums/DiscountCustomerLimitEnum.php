<?php

namespace App\Enums;

/**
 * Enum for defining customer usage limits for a discount.
 */
enum DiscountCustomerLimitEnum: string
{
    case LIMITED = 'LIMITED'; // Each customer can use the discount only once
    case UNLIMITED = 'UNLIMITED'; // No restrictions on customer usage
}
