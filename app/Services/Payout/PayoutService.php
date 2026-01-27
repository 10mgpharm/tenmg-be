<?php

declare(strict_types=1);

namespace App\Services\Payout;

use App\Enums\TransactionCategory;
use App\Models\Business;
use App\Models\ServiceProvider;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\WalletService;
use App\Traits\ThrowsApiExceptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PayoutService
{
    use ThrowsApiExceptions;

    public function __construct(
        protected WalletService $walletService,
        protected PayoutProviderService $providerService
    ) {}

    public function listBanks(string $country = 'NG', string $currency = 'NGN'): array
    {
        $provider = $this->providerService->getProvider($currency);
        $response = $provider->listBanks($country, $currency);

        if (! $response['success']) {
            $this->throwApiException(
                $response['message'] ?? 'Failed to fetch banks',
                $response['error_code'] ?? 400,
                'bank_fetch_failed'
            );
        }

        return $response['data'] ?? [];
    }

    public function verifyAccount(
        string $accountNumber,
        string $bankCode,
        string $currency = 'NGN',
        string $accountType = 'nuban'
    ): array {
        $provider = $this->providerService->getProvider($currency);
        $response = $provider->verifyBankAccount($accountNumber, $bankCode, $currency, $accountType);

        if (! $response['success']) {
            $this->throwApiException(
                $response['message'] ?? 'Failed to verify account',
                $response['error_code'] ?? 400,
                'account_verification_failed'
            );
        }

        $raw = $response['data'] ?? [];

        // Look up bank name if not provided in response
        $bankName = $raw['bankName'] ?? $raw['bank_name'] ?? null;

        if (! $bankName) {
            $bankName = $this->getBankNameByCode($bankCode, $currency);
        }

        return [
            'account_name' => $raw['accountName'] ?? $raw['account_name'] ?? $raw['accountTitle'] ?? null,
            'bank_name' => $bankName,
            'account_number' => $accountNumber,
            'bank_code' => $bankCode,
            'account_type' => $accountType,
            'currency' => $currency,
            'session_id' => $raw['sessionId'] ?? $raw['session_id'] ?? $raw['reference'] ?? null,
            'raw' => $raw,
        ];
    }

    public function payoutToBank(
        Business $business,
        Wallet $wallet,
        float $amount,
        array $bankDetails,
        ?string $narration = null,
        ?string $customerEmail = null,
        ?string $customerPhone = null
    ): array {
        if ($wallet->business_id !== $business->id) {
            $this->throwApiException('Unauthorized wallet access', 403, 'unauthorized_wallet');
        }

        if ($amount <= 0) {
            $this->throwApiException('Amount must be greater than zero', 400, 'invalid_amount');
        }

        if (! $this->walletService->hasSufficientBalance($wallet, $amount)) {
            $this->throwApiException('Insufficient funds', 400, 'insufficient_funds');
        }

        $currencyCode = $wallet->currency?->code ?? $wallet->currency?->slug ?? 'NGN';
        if (strtoupper($currencyCode) !== strtoupper($bankDetails['currency'] ?? 'NGN')) {
            $this->throwApiException('Currency mismatch', 400, 'currency_mismatch');
        }

        $provider = $this->providerService->getProvider($currencyCode);
        $verification = $this->verifyAccount(
            $bankDetails['account_number'],
            $bankDetails['bank_code'],
            $currencyCode,
            $bankDetails['account_type'] ?? 'nuban'
        );

        $accountName = $verification['account_name'] ?? $bankDetails['account_name'] ?? null;
        if (! $accountName) {
            $this->throwApiException('Failed to verify account name', 400, 'account_verification_failed');
        }

        $reference = 'WIT-'.Str::upper(Str::random(8)).'-'.date('YmdHis');

        $providerModel = ServiceProvider::where('slug', $provider->getProviderSlug())
            ->where('status', true)
            ->first();

        if (! $providerModel) {
            $this->throwApiException('Payment provider not available', 500, 'provider_unavailable');
        }

        return DB::transaction(function () use (
            $business,
            $wallet,
            $provider,
            $providerModel,
            $amount,
            $bankDetails,
            $accountName,
            $reference,
            $narration,
            $verification,
            $customerEmail,
            $customerPhone
        ) {
            $transaction = Transaction::create([
                'business_id' => $business->id,
                'wallet_id' => $wallet->id,
                'currency_id' => $wallet->currency_id,
                'transaction_category' => TransactionCategory::DEBIT,
                'transaction_type' => 'withdrawal',
                'transaction_method' => 'bank_transfer',
                'transaction_reference' => $reference,
                'transaction_narration' => $narration ?? 'Withdrawal to bank account',
                'transaction_description' => $narration ?? 'Withdrawal to bank account',
                'amount' => -$amount,
                'processor' => $providerModel->id,
                'processor_reference' => null,
                'status' => 'pending',
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance,
                'transaction_data' => [
                    'bank_account' => [
                        'account_number' => $bankDetails['account_number'],
                        'bank_code' => $bankDetails['bank_code'],
                        'account_name' => $accountName,
                        'bank_name' => $bankDetails['bank_name'] ?? null,
                    ],
                ],
            ]);

            $this->walletService->debitWallet(
                wallet: $wallet,
                amount: $amount,
                transactionReference: $reference,
                transactionId: $transaction->id
            );

            $response = $provider->bankTransfer(
                sourceWallet: $wallet,
                bankDetails: [
                    'account_name' => $accountName,
                    'account_number' => $bankDetails['account_number'],
                    'bank_code' => $bankDetails['bank_code'],
                    'bank_name' => $bankDetails['bank_name'] ?? null,
                    'country_code' => $bankDetails['country'] ?? $bankDetails['country_code'] ?? 'NG',
                    'account_type' => $bankDetails['account_type'] ?? 'nuban',
                ],
                amount: $amount,
                currency: $wallet->currency?->code ?? 'NGN',
                reference: $reference,
                metadata: [
                    'narration' => $narration ?? 'Withdrawal to bank account',
                    'customer_email' => $customerEmail,
                    'customer_phone' => $customerPhone,
                    'customer_name' => $business->name,
                    'business_name' => $business->name,
                    'customer_first_name' => explode(' ', trim($business->name))[0] ?? $business->name,
                    'customer_last_name' => explode(' ', trim($business->name))[1] ?? $business->name,
                ],
                nameEnquiryReference: $verification['session_id'] ?? null
            );

            if (! $response['success']) {
                Log::error('Payout initiation failed', [
                    'business_id' => $business->id,
                    'wallet_id' => $wallet->id,
                    'reference' => $reference,
                    'response' => $response,
                ]);

                $transaction->update([
                    'status' => 'failed',
                    'transaction_data' => array_merge($transaction->transaction_data ?? [], [
                        'provider_response' => $response,
                    ]),
                ]);

                // Refund wallet immediately on failure
                $this->walletService->creditWallet(
                    wallet: $wallet,
                    amount: $amount,
                    transactionReference: $reference.'-refund',
                    transactionId: $transaction->id
                );

                $this->throwApiException(
                    $response['message'] ?? 'Payout initiation failed',
                    $response['error_code'] ?? 400,
                    'payout_failed',
                    $response
                );
            }

            $transaction->update([
                'status' => $response['status'] ?? 'pending',
                'processor_reference' => $response['reference'] ?? $reference,
                'transaction_data' => array_merge($transaction->transaction_data ?? [], [
                    'provider_response' => $response,
                ]),
            ]);

            return [
                'success' => true,
                'reference' => $response['reference'] ?? $reference,
                'status' => $response['status'] ?? 'pending',
                'amount' => $amount,
                'currency' => $wallet->currency?->code ?? 'NGN',
                'provider' => $provider->getProviderSlug(),
            ];
        });
    }

    /**
     * Get bank name by bank code
     */
    private function getBankNameByCode(string $bankCode, string $currency = 'NGN'): ?string
    {
        try {
            $banks = $this->listBanks('NG', $currency);

            if (is_array($banks)) {
                foreach ($banks as $bank) {
                    $code = $bank['code'] ?? $bank['bankCode'] ?? $bank['code'] ?? null;
                    if ($code === $bankCode) {
                        return $bank['name'] ?? $bank['bankName'] ?? $bank['bank_name'] ?? null;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to lookup bank name', [
                'bank_code' => $bankCode,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
