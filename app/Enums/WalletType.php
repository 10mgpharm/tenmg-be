<?php

namespace App\Enums;

enum WalletType: string
{
    case VENDOR_PAYOUT_WALLET = 'vendor_payout_wallet';
    case LENDER_WALLET = 'lender_wallet';
    case ADMIN_WALLET = 'admin_wallet';
}
