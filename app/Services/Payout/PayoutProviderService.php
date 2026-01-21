<?php

declare(strict_types=1);

namespace App\Services\Payout;

use App\Models\Currency;
use App\Traits\ThrowsApiExceptions;

class PayoutProviderService
{
    use ThrowsApiExceptions;

    public function getProvider(string $currencyCode = 'NGN'): PayoutProviderInterface
    {
        $currency = Currency::where('code', strtoupper($currencyCode))->first();

        if (! $currency) {
            $this->throwApiException('Unsupported currency', 400, 'unsupported_currency');
        }

        // For now, Fincra is the only payout provider we wire
        // In future, decide based on currency mapping
        return new FincraPayoutProvider;
    }
}
