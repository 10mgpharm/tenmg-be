<?php

declare(strict_types=1);

namespace App\Services\Payout;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FincraPayoutProvider extends AbstractPayoutProvider
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $businessId;

    protected int $timeout;

    protected int $retries;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.fincra.url') ?? config('services.fincra.base_url'), '/');
        $this->apiKey = config('services.fincra.secret') ?? config('services.fincra.api_key');
        $this->businessId = config('services.fincra.business_id');
        $this->timeout = (int) config('services.fincra.timeout', 30);
        $this->retries = (int) config('services.fincra.retries', 3);
    }

    public function getProviderSlug(): string
    {
        return 'fincra';
    }

    public function listBanks(string $country, string $currency): array
    {
        $endpoint = "{$this->baseUrl}/core/banks";

        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'accept' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->retry($this->retries, 500)
                ->get($endpoint, [
                    'currency' => strtoupper($currency),
                    'country' => strtoupper($country),
                ]);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => $response->json('message') ?? 'Failed to fetch banks',
                    'error_code' => $response->status(),
                ];
            }

            $data = $response->json();

            return [
                'success' => true,
                'data' => $data['data'] ?? $data ?? [],
                'message' => $data['message'] ?? 'Banks fetched',
            ];
        } catch (\Throwable $e) {
            Log::error('Fincra list banks failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to fetch banks',
                'error_code' => 500,
            ];
        }
    }

    public function verifyBankAccount(
        string $accountNumber,
        string $bankCode,
        string $currency,
        string $accountType = 'nuban'
    ): array {
        $endpoint = "{$this->baseUrl}/core/accounts/resolve";

        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->retry($this->retries, 500)
                ->post($endpoint, [
                    'accountNumber' => $accountNumber,
                    'bankCode' => $bankCode,
                    'type' => $accountType,
                    'currency' => strtoupper($currency),
                ]);

            if (! $response->successful()) {
                $body = $response->json();

                // Extract the actual error message from Fincra response
                $errorMessage = $body['error']
                    ?? $body['message']
                    ?? ($body['data']['error'] ?? null)
                    ?? 'Account could not be resolved. Please check your account number and bank code and try again.';

                // Clean up the message - remove any technical details
                $errorMessage = trim($errorMessage);

                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'error_code' => $response->status(),
                    'data' => $body,
                ];
            }

            $data = $response->json();
            $payload = $data['data'] ?? $data;

            return [
                'success' => true,
                'data' => $payload,
                'message' => $data['message'] ?? 'Account verified',
            ];
        } catch (\Throwable $e) {
            Log::error('Fincra verify account failed', [
                'error' => $e->getMessage(),
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
            ]);

            // Try to extract clean error message from exception if it contains JSON
            $errorMessage = 'Account could not be resolved. Please check your account number and bank code and try again.';

            $exceptionMessage = $e->getMessage();

            // Try to extract JSON error from exception message (handles HTTP client exceptions)
            if (preg_match('/"error"\s*:\s*"([^"]+)"/', $exceptionMessage, $matches)) {
                $errorMessage = $matches[1];
            } elseif (preg_match('/error[":\s]+"([^"]+)"/i', $exceptionMessage, $matches)) {
                $errorMessage = $matches[1];
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'error_code' => 500,
            ];
        }
    }

    public function bankTransfer(
        Wallet $sourceWallet,
        array $bankDetails,
        float $amount,
        string $currency,
        string $reference,
        array $metadata = [],
        ?string $nameEnquiryReference = null
    ): array {
        $endpoint = "{$this->baseUrl}/disbursements/payouts";

        $payload = [
            'amount' => $amount,
            'business' => $this->businessId,
            'customerReference' => $reference,
            'description' => $metadata['narration'] ?? 'Payout to bank account',
            'destinationCurrency' => strtoupper($currency),
            'sourceCurrency' => strtoupper($currency),
            'paymentDestination' => 'bank_account',
            'beneficiary' => [
                'accountHolderName' => $bankDetails['account_name'] ?? $bankDetails['accountName'] ?? null,
                'accountNumber' => $bankDetails['account_number'] ?? $bankDetails['accountNumber'] ?? null,
                'bankCode' => $bankDetails['bank_code'] ?? $bankDetails['bankCode'] ?? null,
                'country' => $bankDetails['country_code'] ?? 'NG',
                'type' => 'individual',
                'firstName' => $metadata['customer_first_name'] ?? null,
                'lastName' => $metadata['customer_last_name'] ?? null,
            ],
            'sender' => [
                'name' => $metadata['customer_name'] ?? $metadata['business_name'] ?? 'Tenmg User',
                'email' => $metadata['customer_email'] ?? null,
                'phone' => $metadata['customer_phone'] ?? null,
                'country' => $bankDetails['country_code'] ?? 'NG',
                'type' => 'business',
            ],
            'customerName' => $metadata['customer_name'] ?? $metadata['business_name'] ?? 'Tenmg User',
        ];

        if ($nameEnquiryReference) {
            $payload['nameEnquiryReference'] = $nameEnquiryReference;
        }

        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->retry($this->retries, 500)
                ->post($endpoint, $payload);

            $body = $response->json();

            if (! $response->successful()) {
                // Extract the actual error message from Fincra response
                $errorMessage = $body['error']
                    ?? $body['message']
                    ?? ($body['data']['error'] ?? null)
                    ?? 'Payout initiation failed';

                // Clean up the message
                $errorMessage = trim($errorMessage);

                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'error_code' => $response->status(),
                    'data' => $body,
                    'reference' => $reference,
                ];
            }

            return [
                'success' => true,
                'message' => $body['message'] ?? 'Payout initiated',
                'reference' => $body['reference'] ?? $reference,
                'status' => $body['status'] ?? 'pending',
                'data' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('Fincra bank transfer failed', [
                'error' => $e->getMessage(),
                'wallet_id' => $sourceWallet->id,
                'reference' => $reference,
            ]);

            // Try to extract clean error message from exception if it contains JSON
            $errorMessage = 'Unable to initiate payout. Please try again later.';

            $exceptionMessage = $e->getMessage();

            // Try to extract JSON error from exception message
            if (preg_match('/"error"\s*:\s*"([^"]+)"/', $exceptionMessage, $matches)) {
                $errorMessage = $matches[1];
            } elseif (preg_match('/error[":\s]+"([^"]+)"/i', $exceptionMessage, $matches)) {
                $errorMessage = $matches[1];
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'error_code' => 500,
                'reference' => $reference,
            ];
        }
    }

    public function checkTransactionStatus(Transaction $transaction): array
    {
        $endpoint = "{$this->baseUrl}/disbursements/payouts/{$transaction->processor_reference}";

        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'accept' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->retry($this->retries, 500)
                ->get($endpoint);

            $body = $response->json();

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => $body['message'] ?? 'Status check failed',
                    'error_code' => $response->status(),
                    'data' => $body,
                ];
            }

            return [
                'success' => true,
                'message' => $body['message'] ?? 'Status retrieved',
                'status' => $body['status'] ?? null,
                'data' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('Fincra status check failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id,
            ]);

            return [
                'success' => false,
                'message' => 'Unable to check status',
                'error_code' => 500,
            ];
        }
    }
}
