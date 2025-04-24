<?php

namespace App\Constants;

/**
 * Class WalletConstants
 * Contains constant values for wallet groups and types.
 */
class EcommerceWalletConstants
{
     // When order completes successfully (credit supplier)
    const SUPPLIER_TXN_GROUP_ORDER_PAYMENT = 'CREDIT_ON_ORDER_COMPLETION';

    // When order is canceled (debit supplier)
    const SUPPLIER_TXN_GROUP_ORDER_CANCELLATION = 'DEBIT_ON_ORDER_CANCELLATION';

     // When order completes successfully (credit tenmg)
    const TENMG_TXN_GROUP_ORDER_PAYMENT = 'CREDIT_COMMISSION_ON_ORDER_COMPLETION';

    // When order is canceled (debit tenmg)
    const TENMG_TXN_GROUP_ORDER_CANCELLATION = 'DEBIT_COMMISSION_ON_ORDER_CANCELLATION';

    // Wallet types
    const TXN_TYPE_CREDIT = 'CREDIT';
    const TXN_TYPE_DEBIT = 'DEBIT';
}