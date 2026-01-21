<?php

declare(strict_types=1);

namespace App\Services\Payout;

use App\Models\ServiceProvider;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

abstract class AbstractPayoutProvider implements PayoutProviderInterface
{
    protected ?ServiceProvider $serviceProvider = null;

    public function getServiceProvider(): ServiceProvider
    {
        if (! $this->serviceProvider) {
            $this->serviceProvider = ServiceProvider::where('slug', $this->getProviderSlug())
                ->where('status', true)
                ->firstOrFail();
        }

        return $this->serviceProvider;
    }

    public function getDatabaseProviderId(): string
    {
        return $this->getServiceProvider()->id;
    }

    public function getProviderName(): string
    {
        return ucfirst($this->getProviderSlug());
    }

    public function mobileMoneyTransfer(
        Wallet $sourceWallet,
        array $mobileDetails,
        float $amount,
        string $currency,
        string $reference,
        array $metadata = []
    ): array {
        Log::info('Provider does not support mobile money transfers', [
            'provider' => $this->getProviderName(),
            'wallet_id' => $sourceWallet->id,
            'reference' => $reference,
        ]);

        return [
            'success' => false,
            'message' => 'Mobile money transfers not supported',
            'reference' => $reference,
            'status' => 'failed',
        ];
    }

    public function checkTransactionStatus(Transaction $transaction): array
    {
        return [
            'success' => false,
            'message' => 'Status check not implemented for '.$this->getProviderName(),
            'reference' => $transaction->transaction_reference,
            'status' => 'unknown',
        ];
    }
}
