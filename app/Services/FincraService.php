<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FincraService extends AbstractKycProvider
{
    protected string $baseUrl;

    protected string $businessId;

    protected string $apiKey;

    protected int $timeout;

    protected int $retries;

    /**
     * Initialize the Fincra service with configuration
     */
    public function __construct()
    {
        $this->baseUrl = config('services.fincra.base_url');
        $this->apiKey = config('services.fincra.api_key');
        $this->businessId = config('services.fincra.business_id');
        $this->timeout = intval(config('services.fincra.timeout', 30));
        $this->retries = intval(config('services.fincra.retries', 3));
    }

    /**
     * Resolves BVN information using Fincra API
     *
     * @throws \Exception
     */
    public function resolveBvn(string $bvn): array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'api-key' => $this->apiKey,
            ])->post("{$this->baseUrl}/core/bvn-verification", [
                'bvn' => $bvn,
                'business' => $this->businessId,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $responseData = $data['data']['response'] ?? $data;

                return [
                    'success' => true,
                    'data' => $responseData,
                    'message' => $data['message'] ?? 'BVN resolved successfully',
                    'provider_id' => $this->getDatabaseProviderId(),
                ];
            }

            Log::error('Fincra BVN resolution failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'BVN resolution failed',
                'error_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Exception in Fincra BVN resolution: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception('Fincra BVN resolution failed: '.$e->getMessage());
        }
    }

    /**
     * Create a virtual account using Fincra API
     *
     * @param  string  $accountType  Account type: 'individual' or 'corporate'
     * @param  string  $bvn  Bank verification number
     * @param  string  $externalReference  External reference for the account (e.g. wallet ID)
     * @param  string  $channel  Bank channel (default: 'globus' for individual, 'wema' for corporate)
     * @param  string  $currency  Currency code (default: 'ngn')
     * @param  string|null  $firstName  First name (required for individual)
     * @param  string|null  $lastName  Last name (required for individual)
     * @param  string|null  $businessName  Business name (required for corporate)
     * @param  string|null  $bvnName  Name on BVN (required for corporate, must match BVN record)
     * @param  string|null  $email  Email (optional)
     * @param  string|null  $dateOfBirth  Date of birth (optional for corporate, format: 'DD-MM-YYYY')
     */
    public function createVirtualAccount(
        string $accountType = 'individual',
        string $bvn = '',
        string $externalReference = '',
        string $channel = '',
        string $currency = 'ngn',
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $businessName = null,
        ?string $bvnName = null,
        ?string $email = null,
        ?string $dateOfBirth = null
    ): array {
        try {
            // Set default channel based on account type if not provided
            if (empty($channel)) {
                $channel = $accountType === 'corporate' ? 'wema' : 'globus';
            }

            // Build payload based on account type
            $payload = [
                'currency' => strtoupper($currency),
                'accountType' => $accountType,
                'merchantReference' => $externalReference,
            ];

            // Add channel (required for corporate, optional for individual)
            if ($accountType === 'corporate' || ! empty($channel)) {
                $payload['channel'] = $channel;
            }

            // Build KYCInformation based on account type
            if ($accountType === 'corporate') {
                // Corporate account payload
                $payload['KYCInformation'] = [
                    'businessName' => $businessName,
                    'bvn' => $bvn,
                    'bvnName' => $bvnName ?? $businessName, // Default to businessName if bvnName not provided
                ];

                // Add optional fields
                if ($email) {
                    $payload['KYCInformation']['email'] = $email;
                }

                if ($dateOfBirth) {
                    $payload['dateOfBirth'] = $dateOfBirth;
                }
            } else {
                // Individual account payload
                $payload['KYCInformation'] = [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $email ?? '',
                    'bvn' => $bvn,
                ];
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'api-key' => $this->apiKey,
            ])->post("{$this->baseUrl}/profile/virtual-accounts/requests", $payload);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['success']) && $data['success']) {
                    return [
                        'success' => true,
                        'data' => $data['data'],
                        'message' => $data['message'] ?? 'Virtual account created successfully',
                    ];
                }

                Log::error('Fincra virtual account creation failed with unsuccessful response', [
                    'response' => $data,
                ]);

                return [
                    'success' => false,
                    'message' => $data['message'] ?? 'Virtual account creation failed',
                    'error_code' => $response->status(),
                ];
            }

            Log::error('Fincra virtual account creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => $response->json('error') ?? $response->json('message') ?? 'Virtual account creation failed',
                'response' => $response->json(),
                'error_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Exception in Fincra virtual account creation: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Virtual account creation failed: '.$e->getMessage(),
                'error_code' => 500,
            ];
        }
    }

    /**
     * Get the provider slug/identifier
     */
    public function getProviderSlug(): string
    {
        return 'fincra';
    }

    /**
     * Get the provider display name
     * Optional override if you want a different name than the capitalized slug
     */
    public function getProviderName(): string
    {
        return 'Fincra';
    }
}
