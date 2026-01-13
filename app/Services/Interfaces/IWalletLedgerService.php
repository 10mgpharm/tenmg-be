<?php

namespace App\Services\Interfaces;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletLedger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface IWalletLedgerService defines the contract for wallet ledger operations
 */
interface IWalletLedgerService
{
    /**
     * Create a ledger entry for a wallet transaction
     */
    public function createLedgerEntry(
        Wallet $wallet,
        string $transactionType,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        string $transactionReference,
        ?Transaction $transaction = null
    ): WalletLedger;

    /**
     * Get ledger entries for a wallet
     */
    public function getLedgerEntries(Wallet $wallet, ?string $dateFrom = null, ?string $dateTo = null, int $perPage = 15): LengthAwarePaginator|Collection;

    /**
     * Reconcile wallet balance with ledger entries
     *
     * @return array Contains 'isBalanced', 'expectedBalance', 'actualBalance', 'difference'
     */
    public function reconcileBalance(Wallet $wallet): array;
}
