<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Enums\WalletType;
use App\Models\Business;
use App\Models\Wallet;

interface IWalletService
{
    /**
     * Credit a wallet with the specified amount
     */
    public function creditWallet(
        Wallet $wallet,
        float $amount,
        string $transactionReference,
        string $transactionId
    ): bool;

    /**
     * Debit a wallet with the specified amount
     */
    public function debitWallet(
        Wallet $wallet,
        float $amount,
        string $transactionReference,
        string $transactionId
    ): bool;

    /**
     * Check if wallet has sufficient balance for a transaction
     */
    public function hasSufficientBalance(Wallet $wallet, float $amount): bool;

    /**
     * Get current wallet balance (fresh from database)
     */
    public function getCurrentBalance(Wallet $wallet): float;

    /**
     * Create a main wallet for a business with default currency
     *
     * @param  string  $currencyCode  Default currency code (e.g., 'NGN')
     * @param  WalletType|null  $walletType  Wallet type (defaults to ADMIN_WALLET)
     */
    public function createMainWallet(
        Business $business,
        string $currencyCode = 'NGN',
        ?WalletType $walletType = null
    ): Wallet;

    /**
     * Create a secondary wallet for a business with specified currency
     *
     * @param  string  $currencyCode  Currency code (e.g., 'USD', 'EUR')
     * @param  WalletType  $walletType  Wallet type
     */
    public function createSecondaryWallet(
        Business $business,
        string $currencyCode,
        WalletType $walletType
    ): Wallet;
}
