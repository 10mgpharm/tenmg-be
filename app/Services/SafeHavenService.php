<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Propaganistas\LaravelPhone\PhoneNumber;

class SafeHavenService extends AbstractKycProvider
{
    protected string $baseUrl;

    protected string $clientId;

    protected string $clientAssertion;

    protected int $timeout;

    protected int $retries;

    protected array $accounts;

    public function __construct()
    {
        // Provide default values to prevent null assignment to string properties
        $this->baseUrl = config('services.safehaven.base_url') ?? 'https://api.sandbox.safehavenmfb.com';
        $this->clientId = config('services.safehaven.client_id') ?? '';
        $this->clientAssertion = config('services.safehaven.client_assertion') ?? '';
        $this->timeout = intval(config('services.safehaven.timeout', 30));
        $this->retries = intval(config('services.safehaven.retries', 3));
        $this->accounts = config('services.safehaven.accounts', []);
    }

    /**
     * Check if SafeHaven service is properly configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->baseUrl) && ! empty($this->clientId) && ! empty($this->clientAssertion);
    }

    /**
     * Get authentication token, refreshing if necessary.
     */
    protected function getToken(): string
    {
        if (Cache::has('safehaven_access_token')) {
            return Cache::get('safehaven_access_token');
        }

        if (Cache::has('safehaven_refresh_token')) {
            $refreshedToken = $this->refreshToken(Cache::get('safehaven_refresh_token'));
            if ($refreshedToken['success']) {
                return $refreshedToken['access_token'];
            }
        }

        return $this->getNewToken();
    }

    /**
     * Get a new token using client credentials.
     */
    protected function getNewToken(): string
    {
        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retries, 1000)
                ->post("{$this->baseUrl}/oauth2/token", [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_assertion' => $this->clientAssertion,
                    'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->cacheTokens($data);

                return $data['access_token'];
            }

            Log::error('Failed to get Safe Haven token', ['status' => $response->status()]);

            return '';
        } catch (\Exception $e) {
            Log::error('Exception while getting Safe Haven token: '.$e->getMessage());

            return '';
        }
    }

    /**
     * Refresh an existing token.
     */
    protected function refreshToken(string $refreshToken): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retries, 1000)
                ->post("{$this->baseUrl}/oauth2/token", [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id' => $this->clientId,
                    'client_assertion' => $this->clientAssertion,
                    'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->cacheTokens($data);

                return ['success' => true, 'access_token' => $data['access_token']];
            }

            Log::error('Failed to refresh Safe Haven token', ['status' => $response->status()]);

            return ['success' => false];
        } catch (\Exception $e) {
            Log::error('Exception while refreshing Safe Haven token: '.$e->getMessage());

            return ['success' => false];
        }
    }

    /**
     * Cache access and refresh tokens with appropriate expiration times.
     */
    protected function cacheTokens(array $tokenData): void
    {
        $expiresIn = $tokenData['expires_in'] ?? 2400;
        $bufferTime = 300;

        Cache::put('safehaven_access_token', $tokenData['access_token'], now()->addSeconds($expiresIn - $bufferTime));

        if (isset($tokenData['refresh_token'])) {
            Cache::put('safehaven_refresh_token', $tokenData['refresh_token'], now()->addDays(7));
        }
    }

    /**
     * Make an authenticated POST request to the Safe Haven API.
     */
    private function makePostRequest(string $endpoint, array $data): array
    {
        $token = $this->getToken();
        if (empty($token)) {
            return ['success' => false, 'message' => 'Failed to authenticate with Safe Haven'];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retries, 1000)
                ->withToken($token)
                ->withHeaders(['ClientID' => $this->clientId])
                ->post("{$this->baseUrl}/{$endpoint}", $data);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            Log::error("Safe Haven API error for {$endpoint}", ['status' => $response->status()]);

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Operation failed',
                'error_code' => $response->status(),
            ];
        } catch (ConnectionException $e) {
            Log::error("Connection error with Safe Haven API for {$endpoint}: ".$e->getMessage());

            return ['success' => false, 'message' => 'Connection error with Safe Haven service', 'error_code' => 503];
        } catch (RequestException $e) {
            Log::error("Request error with Safe Haven API for {$endpoint}: ".$e->getMessage());

            return [
                'success' => false,
                'message' => $e->response->json('message') ?? 'Error processing request',
                'error_code' => $e->response->status(),
            ];
        } catch (\Exception $e) {
            Log::error("Exception during {$endpoint}: ".$e->getMessage());

            return ['success' => false, 'message' => 'An unexpected error occurred', 'error_code' => 500];
        }
    }

    /**
     * Resolves BVN information using SafeHaven API
     *
     * @throws \Exception
     */
    public function resolveBvn(string $bvn): array
    {
        return $this->initiateIdentityVerification('BVN', $bvn);
    }

    /**
     * Initiate identity verification (BVN, NIN, etc.).
     */
    public function initiateIdentityVerification(string $type, string $number): array
    {
        $data = [
            'type' => $type,
            'number' => $number,
            'debitAccountNumber' => $this->accounts['operations'] ?? null,
        ];

        $result = $this->makePostRequest('identity/v2', $data);
        if ($result['success']) {
            $responseData = $result['data'];
            if (isset($responseData['statusCode']) && $responseData['statusCode'] !== 200) {
                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Verification service returned an error',
                    'error_code' => $responseData['statusCode'],
                ];
            }

            return [
                'success' => true,
                'data' => $responseData['data'],
                'message' => $responseData['message'] ?? 'Identity verification initiated successfully',
                'provider_id' => $this->getDatabaseProviderId(),
            ];
        }

        return $result;
    }

    /**
     * Validate identity verification with OTP.
     */
    public function validateIdentityOtp(string $identityId, string $type, string $otp): array
    {
        $data = [
            'identityId' => $identityId,
            'type' => $type,
            'otp' => $otp,
        ];

        $result = $this->makePostRequest('identity/v2/validate', $data);

        logger()->info('SafeHaven validateIdentityOtp result', ['result' => $result]);

        if ($result['success']) {
            $responseData = $result['data'];
            if (isset($responseData['statusCode']) && $responseData['statusCode'] !== 200) {
                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'OTP validation failed',
                    'error_code' => $responseData['statusCode'],
                ];
            }

            return [
                'success' => true,
                'data' => $responseData['data'],
                'message' => $responseData['message'] ?? 'Identity verification successful',
            ];
        }

        return $result;
    }

    /**
     * Create a sub-account.
     */
    public function createSubAccount(
        string|PhoneNumber $phoneNumber,
        string $emailAddress,
        string $externalReference,
        string $identityType,
        string $identityId
    ): array {
        $data = compact(
            'phoneNumber',
            'emailAddress',
            'externalReference',
            'identityType',
            'identityId'
        );

        if (! empty($this->accounts['deposit'])) {
            $data['autoSweep'] = true;
            $data['autoSweepDetails'] = [
                'schedule' => 'Instant',
                'accountNumber' => $this->accounts['deposit'],
            ];
        }

        $result = $this->makePostRequest('accounts/v2/subaccount', $data);
        if ($result['success']) {
            return [
                'success' => true,
                'data' => $result['data']['data'] ?? $result['data'],
            ];
        }

        return $result;
    }

    /**
     * Get the provider slug/identifier
     */
    public function getProviderSlug(): string
    {
        return 'safehaven';
    }

    /**
     * Get the provider display name
     */
    public function getProviderName(): string
    {
        return 'Safe Haven';
    }
}
