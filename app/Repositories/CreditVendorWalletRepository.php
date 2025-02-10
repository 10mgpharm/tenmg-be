<?php

namespace App\Repositories;

use App\Models\Business;
use App\Models\CreditVendorWallets;

class CreditVendorWalletRepository
{
    public function createVendorWallet(Business $business): ?bool
    {
        $data = [
            'current_balance' => 0,
            'prev_balance' => 0,
            'last_transaction_ref' => null,
        ];

        $payoutWallet = CreditVendorWallets::updateOrCreate(
            ['vendor_id' => $business->id, 'type' => 'payout'],
            ['type' => 'payout', ...$data]
        );

        $creditVoucherWallet = CreditVendorWallets::updateOrCreate(
            ['vendor_id' => $business->id, 'type' => 'credit_voucher'],
            ['type' => 'credit_voucher', ...$data]
        );

        return ($payoutWallet && $creditVoucherWallet) ? true : false;
    }
}
