<?php

namespace App\Constants;

/**
 * Class WalletConstants
 * Contains constant values for wallet groups and types.
 */
class EcommerceWalletConstants
{
    // Wallet groups
    const TXN_GROUP_ORDER_PAYMENT = 'ORDER_PAYMENT';
    const TXN_GROUP_ORDER_CANCELLATION = 'ORDER_CANCELLATION';

    // Wallet types
    const TXN_TYPE_CREDIT = 'CREDIT';
    const TXN_TYPE_DEBIT = 'DEBIT';
}