<?php

declare(strict_types=1);

namespace App\Webhooks\Fincra;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

class FincraWebhookProfile implements WebhookProfile
{
    public function shouldProcess(Request $request): bool
    {
        $payload = json_decode($request->getContent(), true);

        if (! isset($payload['event'])) {
            return false;
        }

        $validEvents = [
            // Payout events
            'payout.successful',
            'payout.failed',

            // Collection events
            'collection.successful',
            'collection.failed',

            // Virtual account events
            'virtualaccount.approved',
            'virtualaccount.declined',
            'virtualaccount.closed',

            // Conversion events
            'conversion.successful',
            'conversion.failed',
        ];

        return in_array($payload['event'], $validEvents);
    }
}
