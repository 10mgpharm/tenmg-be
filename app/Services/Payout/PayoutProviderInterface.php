<?php

declare(strict_types=1);

namespace App\Services\Payout;

use App\Models\ServiceProvider;
use App\Models\Transaction;
use App\Models\Wallet;

interface PayoutProviderInterface
{
    public function getProviderSlug(): string;

    public function getProviderName(): string;

    public function getServiceProvider(): ServiceProvider;

    public function getDatabaseProviderId(): string;

    public function listBanks(string $country, string $currency): array;

    public function verifyBankAccount(
        string $accountNumber,
        string $bankCode,
        string $currency,
        string $accountType = 'nuban'
    ): array;

    public function bankTransfer(
        Wallet $sourceWallet,
        array $bankDetails,
        float $amount,
        string $currency,
        string $reference,
        array $metadata = [],
        ?string $nameEnquiryReference = null
    ): array;

    public function mobileMoneyTransfer(
        Wallet $sourceWallet,
        array $mobileDetails,
        float $amount,
        string $currency,
        string $reference,
        array $metadata = []
    ): array;

    public function checkTransactionStatus(Transaction $transaction): array;
}
