<?php

namespace App\Services\Credit;

use App\Models\Business;
use App\Models\LenderMonoProfile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LenderMonoRegistrationService
{
    /**
     * Ensure the lender business is registered as a Mono customer.
     * Returns the LenderMonoProfile (with mono_customer_id when available).
     */
    public function createOrGetLenderMonoCustomer(
        Business $lenderBusiness,
        array $profileData,
        string $identityNumber,
        string $identityType
    ): ?LenderMonoProfile {
        try {
            $identityType = strtoupper($identityType);

            // Step 1: Fetch or create local profile
            $profile = LenderMonoProfile::where('lender_business_id', $lenderBusiness->id)->first();

            if ($profile && $profile->mono_customer_id) {
                return $profile;
            }

            if (! $profile) {
                $profile = LenderMonoProfile::create([
                    'lender_business_id' => $lenderBusiness->id,
                    'mono_customer_id' => null,
                    'identity_type' => $identityType,
                    'identity_hash' => LenderMonoProfile::hashIdentity($identityNumber),
                    'name' => $profileData['name'] ?? null,
                    'email' => $profileData['email'] ?? null,
                    'phone' => $profileData['phone'] ?? null,
                    'address' => $profileData['address'] ?? null,
                ]);
            } else {
                $profile->update([
                    'identity_type' => $identityType,
                    'name' => $profileData['name'] ?? $profile->name,
                    'email' => $profileData['email'] ?? $profile->email,
                    'phone' => $profileData['phone'] ?? $profile->phone,
                    'address' => $profileData['address'] ?? $profile->address,
                ]);
            }

            // Step 2: Create Mono customer via /v2/customers
            $monoCustomerId = $this->createMonoCustomerOnMono($profileData, $identityNumber, $identityType);

            if ($monoCustomerId) {
                $profile->update([
                    'mono_customer_id' => $monoCustomerId,
                ]);
            }

            return $profile;
        } catch (\Throwable $e) {
            Log::error('Failed to create or get lender Mono customer', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return null;
        }
    }

    /**
     * Create a Mono customer record for the lender using /v2/customers.
     * Mirrors the logic used in MonoCustomerService but simplified for lender.
     */
    private function createMonoCustomerOnMono(
        array $profileData,
        string $identityNumber,
        string $identityType
    ): ?string {
        try {
            $baseUrl = config('services.mono.base_url');
            $secretKey = config('services.mono.secret_key');

            if (! $secretKey) {
                throw new \Exception('Mono secret key is not configured');
            }

            $url = "{$baseUrl}/v2/customers";

            // Prepare payload according to Mono customer docs
            $payload = [];

            // Name - split full name if first_name/last_name not provided
            if (! empty($profileData['first_name']) || ! empty($profileData['last_name'])) {
                $payload['first_name'] = $profileData['first_name'] ?? '';
                $payload['last_name'] = $profileData['last_name'] ?? '';
            } elseif (! empty($profileData['name'])) {
                // Split full name into first and last name
                $nameParts = explode(' ', trim($profileData['name']), 2);
                $payload['first_name'] = $nameParts[0] ?? '';
                $payload['last_name'] = $nameParts[1] ?? '';
            } else {
                $payload['first_name'] = '';
                $payload['last_name'] = '';
            }

            // Email
            if (! empty($profileData['email'])) {
                $payload['email'] = $profileData['email'];
            }

            // Phone
            if (! empty($profileData['phone'])) {
                $payload['phone'] = $profileData['phone'];
            }

            // Address (max 100 chars)
            if (! empty($profileData['address'])) {
                $payload['address'] = substr($profileData['address'], 0, 100);
            }

            // Identity (BVN or NIN)
            $payload['identity'] = [
                'type' => strtolower($identityType) === 'nin' ? 'nin' : 'bvn',
                'number' => $identityNumber,
            ];

            $logPayload = $payload;
            if (isset($logPayload['identity']['number'])) {
                $id = $logPayload['identity']['number'];
                $logPayload['identity']['number'] = substr($id, 0, 3).'*****'.substr($id, -3);
            }

            Log::info('Creating lender Mono customer', [
                'url' => $url,
                'payload' => $logPayload,
            ]);

            $response = Http::withHeaders([
                'mono-sec-key' => $secretKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post($url, $payload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            Log::info('Lender Mono customer creation response', [
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'response_data' => $responseData,
            ]);

            // Handle \"customer already exists\" case
            if ($statusCode === 400 && isset($responseData['data']['existing_customer']['id'])) {
                $existingId = $responseData['data']['existing_customer']['id'];

                Log::info('Lender Mono customer already exists, using existing ID', [
                    'mono_customer_id' => $existingId,
                ]);

                return $existingId;
            }

            if ($response->failed()) {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Failed to create lender Mono customer';

                // Check for specific error types that we can handle
                $isRecoverable = false;

                // API key issues
                if ($statusCode === 401) {
                    $errorMessage = 'Mono API authentication failed. Check your API keys.';
                }
                // Rate limiting
                elseif ($statusCode === 429) {
                    $errorMessage = 'Mono API rate limit exceeded. Try again later.';
                }
                // Invalid identity
                elseif ($statusCode === 400 && str_contains(strtolower($errorMessage), 'identity')) {
                    $errorMessage = 'Invalid identity details provided. Check BVN/NIN format and validity.';
                }
                // Duplicate customer (should be handled above, but just in case)
                elseif ($statusCode === 400 && (str_contains(strtolower($errorMessage), 'exist') || str_contains(strtolower($errorMessage), 'duplicate'))) {
                    $errorMessage = 'Customer already exists with this identity.';
                }
                // Service unavailable - could be recoverable
                elseif ($statusCode >= 500) {
                    $errorMessage = 'Mono service temporarily unavailable. Try again later.';
                    $isRecoverable = true;
                }

                Log::error('Lender Mono customer creation failed', [
                    'status_code' => $statusCode,
                    'error_message' => $errorMessage,
                    'is_recoverable' => $isRecoverable,
                    'full_response' => $responseData,
                    'response_body' => $responseBody,
                ]);

                // For recoverable errors, don't fail completely - return null so KYC can proceed
                if ($isRecoverable) {
                    Log::warning('Recoverable Mono customer creation error - allowing KYC to proceed without customer registration');

                    return null;
                }

                return null;
            }

            return $responseData['data']['id'] ?? $responseData['id'] ?? null;
        } catch (\Throwable $e) {
            Log::error('Lender Mono customer creation exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return null;
        }
    }
}
