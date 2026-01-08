<?php

namespace App\Services\Credit;

use App\Models\Business;
use App\Models\LenderBvnLookup;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonoBvnLookupService
{
    /**
     * Step 1: Initiate BVN Lookup
     * https://docs.mono.co/docs/lookup/bvn-igree#step-1-initiate-lookup
     */
    public function initiateLookup(Business $lenderBusiness, string $bvn, string $scope = 'identity'): array
    {
        try {
            $baseUrl = config('services.mono.base_url');
            $secretKey = config('services.mono.secret_key');

            if (! $secretKey) {
                throw new \Exception('Mono secret key is not configured');
            }

            $url = "{$baseUrl}/v2/lookup/bvn/initiate";

            $payload = [
                'bvn' => $bvn,
                'scope' => $scope, // 'identity' or 'bank_accounts'
            ];

            // Log payload with masked BVN for security
            $logPayload = $payload;
            $logPayload['bvn'] = substr($bvn, 0, 3).'*****'.substr($bvn, -3);

            Log::info('Initiating Mono BVN lookup', [
                'url' => $url,
                'payload' => $logPayload,
                'lender_business_id' => $lenderBusiness->id,
            ]);

            $response = Http::withHeaders([
                'mono-sec-key' => $secretKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post($url, $payload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            Log::info('Mono BVN lookup initiation response', [
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'response_data' => $responseData,
            ]);

            if ($response->failed()) {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Failed to initiate BVN lookup';

                Log::error('Mono BVN lookup initiation failed', [
                    'status_code' => $statusCode,
                    'error_message' => $errorMessage,
                    'full_response' => $responseData,
                    'response_body' => $responseBody,
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'status_code' => $statusCode,
                ];
            }

            // Extract session ID and methods from response
            $sessionId = $responseData['data']['session_id'] ?? null;
            $methods = $responseData['data']['methods'] ?? [];

            if (! $sessionId) {
                Log::error('Session ID not found in Mono BVN lookup response', [
                    'status_code' => $statusCode,
                    'response_data' => $responseData,
                ]);

                return [
                    'success' => false,
                    'error' => 'Session ID not found in response',
                    'status_code' => $statusCode,
                ];
            }

            // Create or update lender BVN lookup record
            $bvnLookup = LenderBvnLookup::updateOrCreate(
                ['session_id' => $sessionId],
                [
                    'lender_business_id' => $lenderBusiness->id,
                    'bvn_hash' => LenderBvnLookup::hashBvn($bvn),
                    'scope' => $scope,
                    'status' => 'initiated',
                    'verification_methods' => $methods,
                ]
            );

            Log::info('Mono BVN lookup initiated successfully', [
                'session_id' => $sessionId,
                'lender_business_id' => $lenderBusiness->id,
                'bvn_lookup_id' => $bvnLookup->id,
            ]);

            return [
                'success' => true,
                'session_id' => $sessionId,
                'methods' => $methods,
                'bvn_lookup_id' => $bvnLookup->id,
                'status_code' => $statusCode,
            ];

        } catch (\Exception $e) {
            Log::error('Mono BVN lookup initiation exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Step 2: Verify OTP
     * https://docs.mono.co/docs/lookup/bvn-igree#step-2-verify-otp
     */
    public function verifyOtp(string $sessionId, string $method, ?string $phoneNumber = null): array
    {
        try {
            $baseUrl = config('services.mono.base_url');
            $secretKey = config('services.mono.secret_key');

            if (! $secretKey) {
                throw new \Exception('Mono secret key is not configured');
            }

            $url = "{$baseUrl}/v2/lookup/bvn/verify";

            $payload = [
                'method' => $method, // phone, phone_1, alternate_phone, email
            ];

            // Add phone_number if method is alternate_phone
            if ($method === 'alternate_phone') {
                if (! $phoneNumber) {
                    return [
                        'success' => false,
                        'error' => 'phone_number is required when method is alternate_phone',
                    ];
                }
                $payload['phone_number'] = $phoneNumber;
            }

            Log::info('Verifying Mono BVN lookup OTP', [
                'url' => $url,
                'session_id' => $sessionId,
                'method' => $method,
            ]);

            $response = Http::withHeaders([
                'mono-sec-key' => $secretKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-session-id' => $sessionId,
            ])->post($url, $payload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            Log::info('Mono BVN lookup OTP verification response', [
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'response_data' => $responseData,
            ]);

            if ($response->failed()) {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Failed to verify OTP';

                Log::error('Mono BVN lookup OTP verification failed', [
                    'status_code' => $statusCode,
                    'error_message' => $errorMessage,
                    'full_response' => $responseData,
                    'response_body' => $responseBody,
                ]);

                // Update lookup record with error
                $bvnLookup = LenderBvnLookup::where('session_id', $sessionId)->first();
                if ($bvnLookup) {
                    $bvnLookup->update([
                        'status' => 'failed',
                        'error_message' => $errorMessage,
                    ]);
                }

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'status_code' => $statusCode,
                ];
            }

            // Update lookup record with verification method
            $bvnLookup = LenderBvnLookup::where('session_id', $sessionId)->first();
            if ($bvnLookup) {
                $bvnLookup->update([
                    'status' => 'verified',
                    'verification_method' => $method,
                    'phone_number' => $phoneNumber,
                ]);
            }

            $message = $responseData['message'] ?? 'OTP sent successfully';

            return [
                'success' => true,
                'message' => $message,
                'status_code' => $statusCode,
            ];

        } catch (\Exception $e) {
            Log::error('Mono BVN lookup OTP verification exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Step 3: Fetch BVN Details
     * https://docs.mono.co/docs/lookup/bvn-igree#step-3-fetch-details
     */
    public function fetchDetails(string $sessionId, string $otp): array
    {
        try {
            $baseUrl = config('services.mono.base_url');
            $secretKey = config('services.mono.secret_key');

            if (! $secretKey) {
                throw new \Exception('Mono secret key is not configured');
            }

            $url = "{$baseUrl}/v2/lookup/bvn/details";

            $payload = [
                'otp' => $otp,
            ];

            Log::info('Fetching Mono BVN lookup details', [
                'url' => $url,
                'session_id' => $sessionId,
            ]);

            $response = Http::withHeaders([
                'mono-sec-key' => $secretKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-session-id' => $sessionId,
            ])->post($url, $payload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            Log::info('Mono BVN lookup details response', [
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'response_data' => $responseData,
            ]);

            if ($response->failed()) {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Failed to fetch BVN details';

                Log::error('Mono BVN lookup details fetch failed', [
                    'status_code' => $statusCode,
                    'error_message' => $errorMessage,
                    'full_response' => $responseData,
                    'response_body' => $responseBody,
                ]);

                // Update lookup record with error
                $bvnLookup = LenderBvnLookup::where('session_id', $sessionId)->first();
                if ($bvnLookup) {
                    $bvnLookup->update([
                        'status' => 'failed',
                        'error_message' => $errorMessage,
                    ]);
                }

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'status_code' => $statusCode,
                ];
            }

            // Extract data from response
            $lookupData = $responseData['data'] ?? null;

            // Update lookup record with fetched data
            $bvnLookup = LenderBvnLookup::where('session_id', $sessionId)->first();
            if ($bvnLookup) {
                $bvnLookup->update([
                    'status' => 'completed',
                    'lookup_data' => $lookupData,
                ]);
            }

            return [
                'success' => true,
                'data' => $lookupData,
                'status_code' => $statusCode,
            ];

        } catch (\Exception $e) {
            Log::error('Mono BVN lookup details fetch exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
