<?php

declare(strict_types=1);

namespace App\Webhooks\Fincra\Handlers;

use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class FincraWebhookDispatcher extends ProcessWebhookJob
{
    public function handle(): void
    {
        $payload = $this->webhookCall->payload;
        $event = isset($payload['event']) ? $payload['event'] : null;

        // Log Fincra webhook IP for analysis
        Log::info('Fincra webhook received', [
            'provider' => 'fincra',
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'x_forwarded_for' => request()->header('X-Forwarded-For'),
            'x_real_ip' => request()->header('X-Real-IP'),
            'event' => $event,
            'webhook_id' => $this->webhookCall->id,
            'timestamp' => now()->toISOString(),
        ]);

        if (! $event) {
            Log::error('Fincra webhook received with no event type', [
                'webhook_id' => $this->webhookCall->id,
                'ip' => request()->ip(),
            ]);

            return;
        }

        if (str_starts_with($event, 'payout.')) {
            dispatch(new PayoutWebhookJob($this->webhookCall));
        } elseif (str_starts_with($event, 'collection.')) {
            dispatch(new CollectionWebhookJob($this->webhookCall));
        } elseif (str_starts_with($event, 'virtualaccount.')) {
            dispatch(new VirtualAccountWebhookJob($this->webhookCall));
        } elseif (str_starts_with($event, 'conversion.')) {
            dispatch(new ConversionWebhookJob($this->webhookCall));
        } else {
            Log::info('Unhandled Fincra webhook event type: '.$event, [
                'webhook_id' => $this->webhookCall->id,
            ]);
        }
    }
}
