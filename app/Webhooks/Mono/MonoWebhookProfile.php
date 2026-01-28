<?php

declare(strict_types=1);

namespace App\Webhooks\Mono;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

class MonoWebhookProfile implements WebhookProfile
{
    public function shouldProcess(Request $request): bool
    {
        $payload = json_decode($request->getContent(), true);

        if (! isset($payload['event'])) {
            return false;
        }

        $event = $payload['event'];

        // Process Mono Prove events
        $validEvents = [
            'mono.prove.data_verification_initiated',
            'mono.prove.data_verification_successful',
            'mono.prove.data_verification_cancelled',
            'mono.prove.data_verification_expired',
            'mono.prove.data_verification_rejected',
            'mono.prove.data_verification_awaiting_review',

            // Add other Mono events as needed
            // 'mono.events.account_updated',
            // 'mono.direct_debit.mandate.approved',
        ];

        // Allow all mono.prove.* events and mono.events.* events
        return in_array($event, $validEvents)
            || str_starts_with($event, 'mono.prove.')
            || str_starts_with($event, 'mono.events.');
    }
}
