<?php

namespace App\Enums;

enum WalletType: string
{
    case VENDOR_PAYOUT = 'vendor_payout';
    case VENDOR_CREDIT_VOUCHER = 'vendor_credit_voucher';
    case LENDER_INVESTMENT = 'lender_investment';
    case LENDER_DEPOSIT = 'lender_deposit';
    case LENDER_LEDGER = 'lender_ledger';
    case ADMIN_MAIN = 'admin_main';
}
