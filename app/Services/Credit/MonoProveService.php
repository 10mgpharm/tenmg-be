<?php

namespace App\Services\Credit;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonoProveService
{
    /**
     * Initiate a Mono Prove session.
     * https://docs.mono.co/api/prove/initiate
     */
    public function initiateProveSession(array $payload): array
    {
        try {
            $baseUrl = config('services.mono.base_url');
            $secretKey = config('services.mono.prove_secret_key');

            if (! $secretKey) {
                throw new \Exception('Mono Prove secret key (MONO_PROVE_SEC_KEY) is not configured');
            }

            $url = "{$baseUrl}/v1/prove/initiate";

            Log::info('Mono Prove initiate request', [
                'url' => $url,
                'payload' => $payload,
            ]);

            $response = Http::withHeaders([
                'mono-sec-key' => $secretKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post($url, $payload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            Log::info('Mono Prove initiate response', [
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'response_data' => $responseData,
            ]);

            if ($response->failed()) {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Failed to initiate Mono Prove session';

                return [
                    'success' => false,
                    'status_code' => $statusCode,
                    'error' => $errorMessage,
                    'raw' => $responseData,
                ];
            }

            // Return Mono's full response structure
            return [
                'success' => true,
                'status_code' => $statusCode,
                'mono_response' => $responseData, // Full Mono response structure
                'data' => $responseData['data'] ?? $responseData, // Also include extracted data for backward compatibility
            ];
        } catch (\Throwable $e) {
            Log::error('Mono Prove initiate exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch customer details from Mono Prove using reference.
     * https://docs.mono.co/api/prove/fetch-customer-details
     */
    public function fetchCustomerDetails(string $reference): array
    {
        try {
            $baseUrl = config('services.mono.base_url');
            $secretKey = config('services.mono.prove_secret_key');

            if (! $secretKey) {
                throw new \Exception('Mono Prove secret key (MONO_PROVE_SEC_KEY) is not configured');
            }

            $url = "{$baseUrl}/v1/prove/customers/{$reference}";

            Log::info('Fetching Mono Prove customer details', [
                'url' => $url,
                'reference' => $reference,
            ]);

            $response = Http::withHeaders([
                'mono-sec-key' => $secretKey,
                'accept' => 'application/json',
            ])->get($url);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            Log::info('Mono Prove customer details response', [
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'response_data' => $responseData,
                'reference' => $reference,
            ]);

            if ($response->failed()) {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Failed to fetch customer details';

                return [
                    'success' => false,
                    'status_code' => $statusCode,
                    'error' => $errorMessage,
                    'raw' => $responseData,
                ];
            }

            return [
                'success' => true,
                'status_code' => $statusCode,
                'data' => $responseData['data'] ?? $responseData,
                'mono_response' => $responseData,
            ];
        } catch (\Throwable $e) {
            Log::error('Mono Prove fetch customer details exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'reference' => $reference,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
