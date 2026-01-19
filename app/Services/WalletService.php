<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\WalletType;
use App\Models\Business;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletLedger;
use App\Services\Interfaces\IWalletService;
use App\Traits\ThrowsApiExceptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService implements IWalletService
{
    use ThrowsApiExceptions;

    /**
     * Credit a wallet with the specified amount
     * Enhanced with race condition protection and atomic operations
     */
    public function creditWallet(
        Wallet $wallet,
        float $amount,
        string $transactionReference,
        string $transactionId
    ): bool {
        if ($amount <= 0) {
            $this->throwApiException('Amount must be greater than zero', 400, 'invalid_amount');
        }

        return DB::transaction(function () use ($wallet, $amount, $transactionReference, $transactionId) {
            // Lock the wallet for update to prevent race conditions
            $lockedWallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            if (! $lockedWallet) {
                throw new \Exception('Wallet not found or could not be locked');
            }

            $balanceBefore = $lockedWallet->balance;
            $balanceAfter = $balanceBefore + $amount;

            // Atomic update with balance check
            $updated = Wallet::where('id', $lockedWallet->id)
                ->where('balance', $balanceBefore) // Ensure balance hasn't changed
                ->update([
                    'balance' => $balanceAfter,
                    'updated_at' => now(),
                ]);

            if (! $updated) {
                throw new \Exception('Wallet balance changed during transaction - possible race condition');
            }

            // Create ledger entry
            WalletLedger::create([
                'wallet_id' => $lockedWallet->id,
                'transaction_id' => $transactionId,
                'transaction_reference' => $transactionReference,
                'transaction_type' => 'credit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'created_at' => now(),
            ]);

            Log::info('Wallet credited successfully', [
                'wallet_id' => $lockedWallet->id,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'transaction_reference' => $transactionReference,
            ]);

            return true;
        });
    }

    /**
     * Debit a wallet with the specified amount
     * Enhanced with race condition protection and atomic operations
     */
    public function debitWallet(
        Wallet $wallet,
        float $amount,
        string $transactionReference,
        string $transactionId
    ): bool {
        if ($amount <= 0) {
            $this->throwApiException('Amount must be greater than zero', 400, 'invalid_amount');
        }

        return DB::transaction(function () use ($wallet, $amount, $transactionReference, $transactionId) {
            // Lock the wallet for update to prevent race conditions
            $lockedWallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            if (! $lockedWallet) {
                throw new \Exception('Wallet not found or could not be locked');
            }

            $balanceBefore = $lockedWallet->balance;
            $balanceAfter = $balanceBefore - $amount;

            // Double-check balance with locked wallet
            if ($balanceBefore < $amount) {
                $this->throwApiException('Insufficient funds', 400, 'insufficient_funds');
            }

            // Atomic update with balance check
            $updated = Wallet::where('id', $lockedWallet->id)
                ->where('balance', $balanceBefore) // Ensure balance hasn't changed
                ->update([
                    'balance' => $balanceAfter,
                    'updated_at' => now(),
                ]);

            if (! $updated) {
                throw new \Exception('Wallet balance changed during transaction - possible race condition');
            }

            // Create ledger entry
            WalletLedger::create([
                'wallet_id' => $lockedWallet->id,
                'transaction_id' => $transactionId,
                'transaction_reference' => $transactionReference,
                'transaction_type' => 'debit',
                'amount' => -$amount, // Negative amount for debit
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'created_at' => now(),
            ]);

            Log::info('Wallet debited successfully', [
                'wallet_id' => $lockedWallet->id,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'transaction_reference' => $transactionReference,
            ]);

            return true;
        });
    }

    /**
     * Check if wallet has sufficient balance for a transaction
     * This method should be used BEFORE initiating any debit operation
     */
    public function hasSufficientBalance(Wallet $wallet, float $amount): bool
    {
        // Refresh wallet from database to get latest balance
        $freshWallet = Wallet::find($wallet->id);

        return $freshWallet && $freshWallet->balance >= $amount;
    }

    /**
     * Get current wallet balance (fresh from database)
     */
    public function getCurrentBalance(Wallet $wallet): float
    {
        $freshWallet = Wallet::find($wallet->id);

        return $freshWallet ? (float) $freshWallet->balance : 0.0;
    }

    /**
     * Create a main wallet for a business with default currency
     *
     * @param  string  $currencyCode  Default currency code (e.g., 'NGN')
     * @param  WalletType|null  $walletType  Wallet type (defaults to ADMIN_MAIN)
     */
    public function createMainWallet(
        Business $business,
        string $currencyCode = 'NGN',
        ?WalletType $walletType = null
    ): Wallet {
        // Find the currency by code
        $currency = Currency::where('code', $currencyCode)
            ->where('is_active', true)
            ->first();

        if (! $currency) {
            // Fallback to first active currency if default not found
            $currency = Currency::where('is_active', true)->first();

            if (! $currency) {
                $this->throwApiException(
                    'No active currency found for wallet creation',
                    500,
                    'currency_not_found'
                );
            }
        }

        // Use provided wallet type or default to ADMIN_WALLET
        $type = $walletType ?? WalletType::ADMIN_WALLET;

        // Check if business already has a main wallet for this currency and type
        $existingWallet = $business->wallets()
            ->where('currency_id', $currency->id)
            ->where('wallet_type', $type->value)
            ->first();

        if ($existingWallet) {
            return $existingWallet;
        }

        // Create the main wallet
        $wallet = Wallet::create([
            'business_id' => $business->id,
            'wallet_type' => $type->value,
            'currency_id' => $currency->id,
            'balance' => 0.00,
            'wallet_name' => 'Main Wallet - '.$currency->name,
        ]);

        Log::info('Main wallet created for business', [
            'business_id' => $business->id,
            'wallet_id' => $wallet->id,
            'currency' => $currency->code,
            'wallet_type' => $type->value,
        ]);

        return $wallet;
    }

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
    ): Wallet {
        // Find the currency by code
        $currency = Currency::where('code', $currencyCode)
            ->where('is_active', true)
            ->first();

        if (! $currency) {
            $this->throwApiException(
                'Currency not found or inactive',
                404,
                'currency_not_found',
                ['currency_code' => $currencyCode]
            );
        }

        // Check if business already has a wallet for this currency and type
        $existingWallet = $business->wallets()
            ->where('currency_id', $currency->id)
            ->where('wallet_type', $walletType->value)
            ->first();

        if ($existingWallet) {
            return $existingWallet;
        }

        // Create the wallet
        $wallet = Wallet::create([
            'business_id' => $business->id,
            'wallet_type' => $walletType->value,
            'currency_id' => $currency->id,
            'balance' => 0.00,
            'wallet_name' => ucfirst(str_replace('_', ' ', $walletType->value)).' Wallet - '.$currency->name,
        ]);

        Log::info('Secondary wallet created for business', [
            'business_id' => $business->id,
            'wallet_id' => $wallet->id,
            'currency' => $currency->code,
            'wallet_type' => $walletType->value,
        ]);

        return $wallet;
    }
}
