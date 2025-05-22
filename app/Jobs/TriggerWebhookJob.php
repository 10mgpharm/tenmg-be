<?php

namespace App\Jobs;

use App\Models\WebHookCallLog;
use App\Repositories\WebhookRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TriggerWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $maxExceptions = 2;
    // public $timeout = 30;
    public $backoff = [5, 10, 15];
    

    public function __construct(
        public string $url,
        public array $payload = [],
        public ?string $secret = null,
        public array $headers = [],
        public int $timeout = 10,
        public string $signatureHeader = 'X-Signature',
        public int $businessId,
        public ?string $eventType
        
    ) {}

    public function handle(WebhookRepository $webhookRepository)
    {
        $result = $this->secret
            ? $webhookRepository->triggerWithSignature(
                $this->url,
                $this->payload,
                $this->secret,
                $this->signatureHeader,
                $this->timeout,
                $this->businessId
              )
            : $webhookRepository->trigger(
                $this->url,
                $this->payload,
                $this->headers,
                $this->timeout,
                $this->businessId
              );

        WebHookCallLog::create([
            'business_id' => $this->businessId,
            'event' => $this->payload['event'],
            'route' => $this->url,
            'request' => $this->eventType,
            'response' => '200',
            'status' => 'successful',
        ]);
    }

    public function failed(\Throwable $exception)
    {
        WebHookCallLog::create([
            'business_id' => $this->businessId,
            'event' => $this->payload['event'],
            'route' => $this->url,
            'request' => $this->eventType,
            'response' => '500',
            'status' => 'failed',
        ]);

        Log::error("Webhook job failed after {$this->tries} attempts", [
            'url' => $this->url,
            'error' => $exception->getMessage(),
            'payload' => $this->payload
        ]);
    }
}