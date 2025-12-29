<?php

namespace App\Services\Credit;

use App\Models\Business;
use App\Models\MonoCustomer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonoCustomerService
{
    /**
     * Create or get Mono customer
     * Returns Mono customer ID
     */
    public function createOrGetMonoCustomer(
        array $profileData,
        string $bvn,
        ?Business $vendorBusiness = null
    ): ?string {
        try {
            // Step 1: Check if customer already exists locally by BVN
            $existingCustomer = MonoCustomer::findByBvn($bvn);

            if ($existingCustomer && $existingCustomer->mono_customer_id) {
                Log::info('Mono customer found locally by BVN', [
                    'mono_customer_id' => $existingCustomer->mono_customer_id,
                    'bvn_masked' => substr($bvn, 0, 3).'*****'.substr($bvn, -3),
                ]);

                return $existingCustomer->mono_customer_id;
            }

            // Step 2: Create customer record locally first (without mono_customer_id)
            $monoCustomer = null;
            if ($existingCustomer) {
                // Customer exists locally but doesn't have mono_customer_id yet
                // Update with latest data (first_name, last_name, vendor_business_id, etc.)
                $existingCustomer->update([
                    'first_name' => $this->normalizeToString($profileData['first_name'] ?? null) ?: $existingCustomer->first_name,
                    'last_name' => $this->normalizeToString($profileData['last_name'] ?? null) ?: $existingCustomer->last_name,
                    'email' => $this->normalizeToString($profileData['email'] ?? null) ?: $existingCustomer->email,
                    'phone' => $this->normalizeToString($profileData['phone'] ?? null) ?: $existingCustomer->phone,
                    'address' => $this->normalizeToString($profileData['address'] ?? null) ?: $existingCustomer->address,
                    'vendor_business_id' => $vendorBusiness?->id ?? $existingCustomer->vendor_business_id,
                ]);
                $monoCustomer = $existingCustomer;

                Log::info('Existing Mono customer record updated', [
                    'local_id' => $monoCustomer->id,
                ]);
            } else {
                // Create new local customer record
                $monoCustomer = MonoCustomer::create([
                    'mono_customer_id' => null, // Will be updated after Mono API call
                    'bvn_hash' => MonoCustomer::hashBvn($bvn),
                    'first_name' => $this->normalizeToString($profileData['first_name'] ?? null),
                    'last_name' => $this->normalizeToString($profileData['last_name'] ?? null),
                    'email' => $this->normalizeToString($profileData['email'] ?? null),
                    'phone' => $this->normalizeToString($profileData['phone'] ?? null),
                    'address' => $this->normalizeToString($profileData['address'] ?? null),
                    'vendor_business_id' => $vendorBusiness?->id,
                ]);

                Log::info('Local Mono customer record created', [
                    'local_id' => $monoCustomer->id,
                ]);
            }

            // Step 3: Create customer on Mono API (or get existing customer ID)
            $monoCustomerId = $this->createMonoCustomer($profileData, $monoCustomer);

            // Step 4: Update local record with Mono customer ID
            if ($monoCustomerId && $monoCustomer) {
                $monoCustomer->update([
                    'mono_customer_id' => $monoCustomerId,
                ]);

                Log::info('Mono customer ID updated in local record', [
                    'mono_customer_id' => $monoCustomerId,
                    'local_id' => $monoCustomer->id,
                ]);
            }

            return $monoCustomerId;

        } catch (\Exception $e) {
            Log::error('Failed to create or get Mono customer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Create customer on Mono API
     * Returns Mono customer ID on success
     * Handles "customer already exists" response by extracting existing customer ID
     */
    private function createMonoCustomer(array $profileData, ?MonoCustomer $localCustomer = null): ?string
    {
        try {
            $baseUrl = config('services.mono.base_url');
            $secretKey = config('services.mono.secret_key');

            if (! $secretKey) {
                throw new \Exception('Mono secret key is not configured');
            }

            $url = "{$baseUrl}/v2/customers";

            // Prepare customer data for Mono API according to docs
            // https://docs.mono.co/docs/payments/direct-debit/integration-guide-create-customers
            $customerPayload = [];

            // Add name fields (required)
            if (! empty($profileData['first_name']) || ! empty($profileData['last_name'])) {
                $customerPayload['first_name'] = $profileData['first_name'] ?? '';
                $customerPayload['last_name'] = $profileData['last_name'] ?? '';
            } elseif (! empty($profileData['full_name'])) {
                // Split full_name if first_name/last_name not available
                $nameParts = explode(' ', $profileData['full_name'], 2);
                $customerPayload['first_name'] = $nameParts[0] ?? '';
                $customerPayload['last_name'] = $nameParts[1] ?? '';
            }

            // Add contact information (required)
            // Ensure email and phone are strings, not arrays
            $email = $profileData['email'] ?? null;
            if (is_array($email)) {
                $email = $email[0] ?? null;
            }
            if (! empty($email)) {
                $customerPayload['email'] = $email;
            }

            $phone = $profileData['phone'] ?? null;
            if (is_array($phone)) {
                $phone = $phone[0] ?? null;
            }
            if (! empty($phone)) {
                $customerPayload['phone'] = $phone;
            }

            // Add address (required, max 100 characters)
            $address = $profileData['address'] ?? null;
            if (is_array($address)) {
                $address = $address[0] ?? null;
            }
            if (! empty($address)) {
                // Truncate to 100 characters as per Mono API requirement
                $customerPayload['address'] = substr($address, 0, 100);
            }

            // Add BVN in identity object (required)
            if (! empty($profileData['bvn'])) {
                $customerPayload['identity'] = [
                    'type' => 'bvn',
                    'number' => $profileData['bvn'], // No white spaces allowed
                ];
            }

            // Log payload with masked BVN for security
            $logPayload = $customerPayload;
            if (isset($logPayload['identity']['number'])) {
                $bvn = $logPayload['identity']['number'];
                $logPayload['identity']['number'] = substr($bvn, 0, 3).'*****'.substr($bvn, -3);
            }

            Log::info('Creating Mono customer', [
                'url' => $url,
                'payload' => $logPayload,
            ]);

            $response = Http::withHeaders([
                'mono-sec-key' => $secretKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post($url, $customerPayload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            // Log full response for debugging
            Log::info('Mono customer creation response', [
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'response_data' => $responseData,
            ]);

            // Handle "customer already exists" response (status 400)
            if ($statusCode === 400 && isset($responseData['data']['existing_customer']['id'])) {
                $monoCustomerId = $responseData['data']['existing_customer']['id'];

                Log::info('Mono customer already exists, using existing customer ID', [
                    'mono_customer_id' => $monoCustomerId,
                    'message' => $responseData['message'] ?? 'Customer already exists',
                ]);

                return $monoCustomerId;
            }

            if ($response->failed()) {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Failed to create Mono customer';

                Log::error('Mono customer creation failed', [
                    'status_code' => $statusCode,
                    'error_message' => $errorMessage,
                    'full_response' => $responseData,
                    'response_body' => $responseBody,
                ]);

                return null;
            }

            // Extract customer ID from successful response
            // According to docs, response structure is: { "status": "successful", "data": { "id": "..." } }
            $monoCustomerId = $responseData['data']['id'] ?? $responseData['id'] ?? null;

            if (! $monoCustomerId) {
                Log::error('Mono customer ID not found in response', [
                    'status_code' => $statusCode,
                    'response_data' => $responseData,
                    'response_body' => $responseBody,
                ]);

                return null;
            }

            Log::info('Mono customer created successfully', [
                'mono_customer_id' => $monoCustomerId,
            ]);

            return $monoCustomerId;

        } catch (\Exception $e) {
            Log::error('Mono customer creation exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Retrieve a customer from Mono API
     * https://docs.mono.co/api/customer/retrieve-a-customer
     */
    public function retrieveCustomer(string $monoCustomerId): array
    {
        try {
            $baseUrl = config('services.mono.base_url');
            $secretKey = config('services.mono.secret_key');

            if (! $secretKey) {
                throw new \Exception('Mono secret key is not configured');
            }

            $url = "{$baseUrl}/v2/customers/{$monoCustomerId}";

            Log::info('Retrieving Mono customer', [
                'url' => $url,
                'mono_customer_id' => $monoCustomerId,
            ]);

            $response = Http::withHeaders([
                'mono-sec-key' => $secretKey,
                'accept' => 'application/json',
            ])->get($url);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            Log::info('Mono customer retrieval response', [
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'response_data' => $responseData,
            ]);

            if ($response->failed()) {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Failed to retrieve Mono customer';

                Log::error('Mono customer retrieval failed', [
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

            return [
                'success' => true,
                'data' => $responseData['data'] ?? $responseData,
                'status_code' => $statusCode,
            ];

        } catch (\Exception $e) {
            Log::error('Mono customer retrieval exception', [
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
     * Delete a customer from Mono API
     * https://docs.mono.co/api/customer/delete-customer
     */
    public function deleteCustomer(string $monoCustomerId): array
    {
        try {
            $baseUrl = config('services.mono.base_url');
            $secretKey = config('services.mono.secret_key');

            if (! $secretKey) {
                throw new \Exception('Mono secret key is not configured');
            }

            $url = "{$baseUrl}/v2/customers/{$monoCustomerId}";

            Log::info('Deleting Mono customer', [
                'url' => $url,
                'mono_customer_id' => $monoCustomerId,
            ]);

            $response = Http::withHeaders([
                'mono-sec-key' => $secretKey,
                'accept' => 'application/json',
            ])->delete($url);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            Log::info('Mono customer deletion response', [
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'response_data' => $responseData,
            ]);

            if ($response->failed()) {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Failed to delete Mono customer';

                Log::error('Mono customer deletion failed', [
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

            // Also delete from local database if exists
            $localCustomer = MonoCustomer::where('mono_customer_id', $monoCustomerId)->first();
            if ($localCustomer) {
                $localCustomer->delete();
                Log::info('Local Mono customer record deleted', [
                    'mono_customer_id' => $monoCustomerId,
                    'local_id' => $localCustomer->id,
                ]);
            }

            return [
                'success' => true,
                'message' => $responseData['message'] ?? 'Customer deleted successfully',
                'data' => $responseData['data'] ?? $responseData,
                'status_code' => $statusCode,
            ];

        } catch (\Exception $e) {
            Log::error('Mono customer deletion exception', [
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
     * List all customers from Mono API
     * https://docs.mono.co/api/customer/list-all-customers
     */
    public function listAllCustomers(): array
    {
        try {
            $baseUrl = config('services.mono.base_url');
            $secretKey = config('services.mono.secret_key');

            if (! $secretKey) {
                throw new \Exception('Mono secret key is not configured');
            }

            $url = "{$baseUrl}/v2/customers";

            Log::info('Listing all Mono customers', [
                'url' => $url,
            ]);

            $response = Http::withHeaders([
                'mono-sec-key' => $secretKey,
                'accept' => 'application/json',
            ])->get($url);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            Log::info('Mono customers list response', [
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'response_data' => $responseData,
            ]);

            if ($response->failed()) {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Failed to list Mono customers';

                Log::error('Mono customers list failed', [
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

            return [
                'success' => true,
                'data' => $responseData['data'] ?? $responseData,
                'status_code' => $statusCode,
            ];

        } catch (\Exception $e) {
            Log::error('Mono customers list exception', [
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
     * Normalize value to string (handle arrays by taking first element)
     */
    private function normalizeToString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            // If it's an array, get the first element
            $value = $value[0] ?? null;
            if ($value === null) {
                return null;
            }
        }

        // Convert to string and trim
        return trim((string) $value) ?: null;
    }
}
