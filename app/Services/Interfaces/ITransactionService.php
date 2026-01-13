<?php

namespace App\Services\Interfaces;

use App\Enums\TransactionCategory;
use App\Models\Business;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface ITransactionService defines the contract for transaction operations
 */
interface ITransactionService
{
    /**
     * Create a new transaction
     */
    public function createTransaction(Wallet $wallet, Currency $currency, TransactionCategory $category, string $transactionType, float $amount, array $data = []): Transaction;

    /**
     * Get a transaction by ID
     */
    public function getTransaction(string $transactionId): ?Transaction;

    /**
     * List transactions with optional filters
     */
    public function listTransactions(
        ?Business $business = null,
        ?Wallet $wallet = null,
        ?TransactionCategory $category = null,
        ?string $transactionType = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 15
    ): LengthAwarePaginator|Collection;

    /**
     * Update transaction status
     */
    public function updateTransactionStatus(Transaction $transaction, string $status, array $metadata = []): Transaction;
}
