<?php

declare(strict_types=1);

namespace App\Webhooks\Mono\Handlers;

use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class MonoWebhookDispatcher extends ProcessWebhookJob
{
    public function handle(): void
    {
        $payload = $this->webhookCall->payload;
        $event = isset($payload['event']) ? $payload['event'] : null;

        Log::info('Mono webhook received', [
            'provider' => 'mono',
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'event' => $event,
            'webhook_id' => $this->webhookCall->id,
            'timestamp' => now()->toISOString(),
        ]);

        if (! $event) {
            Log::error('Mono webhook received with no event type', [
                'webhook_id' => $this->webhookCall->id,
                'ip' => request()->ip(),
            ]);

            return;
        }

        // Route to specific handlers based on event prefix
        if (str_starts_with($event, 'mono.prove.')) {
            dispatch(new ProveWebhookJob($this->webhookCall));
        }
        // Add other handlers as needed
        // elseif (str_starts_with($event, 'mono.direct_debit.')) {
        //     dispatch(new MandateWebhookJob($this->webhookCall));
        // }
        else {
            Log::info('Unhandled Mono webhook event type: '.$event, [
                'webhook_id' => $this->webhookCall->id,
            ]);
        }
    }
}
