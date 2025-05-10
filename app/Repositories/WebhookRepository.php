<?php

namespace App\Repositories;

use App\Models\WebHookCallLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookRepository
{
    /**
     * Trigger a webhook URL with POST request
     *
     * @param string $url Webhook URL
     * @param array $payload Data to send
     * @param array $headers Custom headers
     * @param int $timeout Request timeout in seconds
     * @return array Response details
     */
    public function trigger(
        string $url,
        array $payload = [],
        array $headers = [],
        int $timeout = 10,
        int $businessId,
    ): array {
        $defaultHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $headers = array_merge($defaultHeaders, $headers);

        try {
            $response = Http::withHeaders($headers)
                ->timeout($timeout)
                ->post($url, $payload);


            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error("Webhook failed to {$url}: " . $e->getMessage());

                $WebHookCallLog = new WebHookCallLog();
                $WebHookCallLog->business_id = $businessId;
                $WebHookCallLog->event = "Loan link generated";
                $WebHookCallLog->route = request()->path();
                $WebHookCallLog->request = request()->method();
                $WebHookCallLog->response = '200';
                $WebHookCallLog->status = 'successful';
                $WebHookCallLog->save();
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    /**
     * Trigger a webhook with signature verification
     *
     * @param string $url Webhook URL
     * @param array $payload Data to send
     * @param string $secret Secret key for signing
     * @param string $signatureHeaderName Header name for signature
     * @param int $timeout Request timeout in seconds
     * @return array Response details
     */
    public function triggerWithSignature(
        string $url,
        array $payload,
        string $secret,
        string $signatureHeaderName = 'X-Signature',
        int $timeout = 10,
        int $businessId
    ): array {
        $payloadString = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadString, $secret);

        return $this->trigger(
            $url,
            $payload,
            [
                $signatureHeaderName => $signature,
                'X-Signature-Algorithm' => 'sha256',
            ],
            $timeout,
            $businessId
        );
    }


}