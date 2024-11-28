<?php

namespace App\Constants;

class RoleConstant
{
    public const ADMIN = 'admin'; // service provider / super admin | can add more users

    public const VENDOR = 'vendor'; //focus on using the credit score system to evaluate their direct customers | can add moren users

    public const SUPPLIER = 'supplier'; //focus on listing their product on the ecommerce platform, get their payouts

    public const CUSTOMER = 'customer'; // Pharmacy / Hospital

    public const OPERATION = 'operation';

    public const SUPPORT = 'support';
}
