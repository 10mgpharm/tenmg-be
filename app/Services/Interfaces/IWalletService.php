<?php

namespace App\Services\Interfaces;

use App\Enums\WalletType;
use App\Models\Business;
use App\Models\Currency;
use App\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface IWalletService defines the contract for wallet operations
 */
interface IWalletService
{
    /**
     * Create a new wallet for a business
     */
    public function createWallet(Business $business, Currency $currency, WalletType $walletType, ?string $walletName = null): Wallet;

    /**
     * Get a wallet by ID
     */
    public function getWallet(string $walletId): ?Wallet;

    /**
     * List wallets with optional filters
     */
    public function listWallets(?Business $business = null, ?WalletType $walletType = null, ?Currency $currency = null, int $perPage = 15): LengthAwarePaginator|Collection;

    /**
     * Update wallet name
     */
    public function updateWallet(Wallet $wallet, string $walletName): Wallet;

    /**
     * Get wallet balance
     */
    public function getWalletBalance(Wallet $wallet): float;

    /**
     * Credit amount to wallet
     */
    public function creditWallet(Wallet $wallet, float $amount, string $transactionType, array $metadata = []): Wallet;

    /**
     * Debit amount from wallet
     */
    public function debitWallet(Wallet $wallet, float $amount, string $transactionType, array $metadata = []): Wallet;
}
