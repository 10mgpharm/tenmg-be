<?php

namespace App\Services\Credit;

use App\Models\LenderMatch;
use App\Models\MonoCustomer;
use App\Models\MonoMandate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonoMandateService
{
    /**
     * Initiate GSM mandate for a lender match
     * https://docs.mono.co/api/direct-debit/mandate/initiate-mandate-authorisation
     * Returns mandate URL for customer authorization
     *
     * @param  array|null  $customerData  Optional customer data if MonoCustomer needs to be created
     *                                    Should include: bvn, first_name, last_name, email, phone, address
     */
    public function initiateMandate(LenderMatch $lenderMatch, ?array $customerData = null): array
    {
        try {
            // Get the MonoCustomer for this vendor (the customer for Mono mandate)
            $monoCustomer = MonoCustomer::where('vendor_business_id', $lenderMatch->vendor_business_id)
                ->whereNotNull('mono_customer_id')
                ->orderBy('created_at', 'desc')
                ->first();

            // Check if we have a Mono customer ID
            if (! $monoCustomer || ! $monoCustomer->mono_customer_id) {
                Log::info('MonoCustomer not found or missing Mono customer ID', [
                    'lender_match_id' => $lenderMatch->id,
                    'vendor_business_id' => $lenderMatch->vendor_business_id,
                    'has_customer' => ! empty($monoCustomer),
                    'has_mono_id' => $monoCustomer ? ! empty($monoCustomer->mono_customer_id) : false,
                ]);

                // MonoCustomer exists but doesn't have mono_customer_id
                // Need to create customer on Mono API
                if ($customerData && isset($customerData['bvn'])) {
                    Log::info('Creating Mono customer from provided data', [
                        'lender_match_id' => $lenderMatch->id,
                        'vendor_business_id' => $lenderMatch->vendor_business_id,
                        'mono_customer_id' => $monoCustomer?->id,
                    ]);

                    // Prepare data for Mono customer creation
                    $monoData = [
                        'first_name' => $customerData['first_name'] ?? $monoCustomer->first_name ?? 'Unknown',
                        'last_name' => $customerData['last_name'] ?? $monoCustomer->last_name ?? 'Unknown',
                        'email' => $customerData['email'] ?? $monoCustomer->email ?? '',
                        'phone' => $customerData['phone'] ?? $monoCustomer->phone ?? '',
                        'address' => $customerData['address'] ?? $monoCustomer->address ?? '',
                        'bvn' => $customerData['bvn'],
                    ];

                    // Create customer on Mono API
                    $monoCustomerId = $this->createCustomerOnMonoAPI($monoData, $customerData['bvn']);

                    if ($monoCustomerId) {
                        // Update MonoCustomer with the Mono customer ID
                        if ($monoCustomer) {
                            $monoCustomer->mono_customer_id = $monoCustomerId;
                            $monoCustomer->save();
                        } else {
                            // Create new MonoCustomer record
                            $monoCustomer = MonoCustomer::create([
                                'mono_customer_id' => $monoCustomerId,
                                'bvn_hash' => MonoCustomer::hashBvn($customerData['bvn']),
                                'first_name' => $monoData['first_name'],
                                'last_name' => $monoData['last_name'],
                                'email' => $monoData['email'],
                                'phone' => $monoData['phone'],
                                'address' => $monoData['address'],
                                'vendor_business_id' => $lenderMatch->vendor_business_id,
                            ]);
                        }

                        Log::info('Mono customer created and MonoCustomer updated', [
                            'lender_match_id' => $lenderMatch->id,
                            'mono_customer_id' => $monoCustomerId,
                            'mono_customer_db_id' => $monoCustomer->id,
                        ]);
                    } else {
                        return [
                            'success' => false,
                            'error' => 'Failed to create customer on Mono API. Please check the BVN and try again.',
                        ];
                    }
                } else {
                    return [
                        'success' => false,
                        'error' => 'Customer has not completed credit check. Mono customer ID is required. Please provide BVN to create customer.',
                    ];
                }
            }

            // Use the mono_customer_id from MonoCustomer
            $monoCustomerIdToUse = $monoCustomer->mono_customer_id;

            if (! $monoCustomerIdToUse) {
                return [
                    'success' => false,
                    'error' => 'Mono customer ID not available after creation attempt.',
                ];
            }

            $baseUrl = config('services.mono.base_url');
            $secretKey = config('services.mono.secret_key');

            if (! $secretKey) {
                throw new \Exception('Mono secret key is not configured');
            }

            $url = "{$baseUrl}/v2/payments/initiate";

            // Calculate dates based on tenor
            $startDate = Carbon::today();
            $endDate = Carbon::today()->addMonths($lenderMatch->default_tenor);

            // Generate unique reference (max 24 chars, alphanumeric only for Mono)
            // Format: MAN + uppercase hash (21 chars) = 24 total
            $uniqueString = $lenderMatch->borrower_reference.$lenderMatch->id.time();
            $mandateReference = 'MAN'.strtoupper(substr(md5($uniqueString), 0, 21));

            // Validate minimum amount for Mono mandates (NGN 20,000)
            $amount = (int) $lenderMatch->amount;
            $minimumAmount = 20000;

            if ($amount < $minimumAmount) {
                return [
                    'success' => false,
                    'error' => "Mandate amount must be at least NGN {$minimumAmount}. Current amount: NGN {$amount}",
                ];
            }

            // Use Tenmg callback URL for processing before redirecting to vendor
            $tenmgCallbackUrl = config('services.tenmg_credit.mandate_callback_url');
            $redirectUrl = $tenmgCallbackUrl ?: ($lenderMatch->callback_url ?? config('app.url'));

            // Prepare mandate payload
            $mandatePayload = [
                'amount' => $amount,
                'type' => 'recurring-debit',
                'method' => 'mandate',
                'mandate_type' => 'gsm', // Global Standing Mandate
                'debit_type' => 'variable', // Variable allows flexible debit amounts
                'description' => "Loan repayment for {$lenderMatch->borrower_reference}",
                'reference' => $mandateReference,
                'redirect_url' => $redirectUrl,
                'customer' => [
                    'id' => $monoCustomerIdToUse,
                ],
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'meta' => [
                    'borrower_reference' => $lenderMatch->borrower_reference,
                    'lender_match_id' => $lenderMatch->id,
                    'vendor_callback_url' => $lenderMatch->callback_url, // Store vendor's callback for later redirect
                ],
            ];

            Log::info('Initiating Mono GSM mandate', [
                'url' => $url,
                'lender_match_id' => $lenderMatch->id,
                'borrower_reference' => $lenderMatch->borrower_reference,
                'payload' => array_merge($mandatePayload, ['customer' => ['id' => $monoCustomerIdToUse]]),
            ]);

            $response = Http::withHeaders([
                'mono-sec-key' => $secretKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post($url, $mandatePayload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            Log::info('Mono mandate initiation response', [
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'response_data' => $responseData,
            ]);

            if ($response->failed()) {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Failed to initiate Mono mandate';

                Log::error('Mono mandate initiation failed', [
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

            // Extract mandate URL from response
            $mandateData = $responseData['data'] ?? $responseData;
            $monoMandateUrl = $mandateData['mono_url'] ?? null;
            $mandateId = $mandateData['id'] ?? $mandateData['mandate_id'] ?? null;

            if (! $monoMandateUrl) {
                Log::error('Mono mandate URL not found in response', [
                    'status_code' => $statusCode,
                    'response_data' => $responseData,
                    'response_body' => $responseBody,
                ]);

                return [
                    'success' => false,
                    'error' => 'Mono mandate URL not found in response',
                ];
            }

            // Generate mandate_id if not provided by Mono
            if (! $mandateId) {
                $mandateId = 'mmc_'.bin2hex(random_bytes(12));
            }

            // Store mandate record in database
            $monoMandate = MonoMandate::create([
                'lender_match_id' => $lenderMatch->id,
                'mono_customer_id' => $monoCustomerIdToUse, // Mono API customer ID
                'mandate_id' => $mandateId,
                'reference' => $mandateReference,
                'mono_url' => $monoMandateUrl,
                'status' => 'pending',
                'amount' => (int) $lenderMatch->amount,
                'currency' => $lenderMatch->currency ?? 'NGN',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'description' => $mandatePayload['description'],
                'redirect_url' => $mandatePayload['redirect_url'],
                'meta' => $mandatePayload['meta'],
                'mono_response' => $responseData,
                'is_mock' => false,
            ]);

            Log::info('Mono GSM mandate initiated successfully', [
                'lender_match_id' => $lenderMatch->id,
                'mandate_id' => $monoMandate->mandate_id,
                'mandate_url' => $monoMandateUrl,
            ]);

            return [
                'success' => true,
                'mandate_url' => $monoMandateUrl,
                'mandate_id' => $monoMandate->mandate_id,
                'status_code' => $statusCode,
            ];

        } catch (\Exception $e) {
            Log::error('Mono mandate initiation exception', [
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
     * Create a customer on Mono API using /v2/customers endpoint
     * https://docs.mono.co/api/customer/create-a-customer
     */
    private function createCustomerOnMonoAPI(array $profileData, string $bvn): ?string
    {
        try {
            $baseUrl = config('services.mono.base_url');
            $secretKey = config('services.mono.secret_key');

            if (! $secretKey) {
                throw new \Exception('Mono secret key is not configured');
            }

            $url = "{$baseUrl}/v2/customers";

            // Prepare payload according to Mono docs
            $payload = [
                'first_name' => $profileData['first_name'] ?? '',
                'last_name' => $profileData['last_name'] ?? '',
                'email' => $profileData['email'] ?? '',
                'phone' => $profileData['phone'] ?? '',
                'address' => substr($profileData['address'] ?? '', 0, 100), // Max 100 chars
                'identity' => [
                    'type' => 'bvn',
                    'number' => $bvn,
                ],
            ];

            $logPayload = $payload;
            $logPayload['identity']['number'] = substr($bvn, 0, 3).'*****'.substr($bvn, -3);

            Log::info('Creating Mono customer via API', [
                'url' => $url,
                'payload' => $logPayload,
            ]);

            $response = Http::withHeaders([
                'mono-sec-key' => $secretKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post($url, $payload);

            $statusCode = $response->status();
            $responseData = $response->json();

            Log::info('Mono customer creation response', [
                'status_code' => $statusCode,
                'response_data' => $responseData,
            ]);

            // Handle "customer already exists" response (status 400)
            if ($statusCode === 400 && isset($responseData['data']['existing_customer']['id'])) {
                $existingId = $responseData['data']['existing_customer']['id'];

                Log::info('Mono customer already exists, using existing ID', [
                    'mono_customer_id' => $existingId,
                ]);

                return $existingId;
            }

            if ($response->failed()) {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Failed to create Mono customer';

                Log::error('Mono customer creation failed', [
                    'status_code' => $statusCode,
                    'error_message' => $errorMessage,
                    'response_data' => $responseData,
                ]);

                return null;
            }

            // Extract customer ID from successful response
            $monoCustomerId = $responseData['data']['id'] ?? $responseData['id'] ?? null;

            if (! $monoCustomerId) {
                Log::error('Mono customer ID not found in response', [
                    'status_code' => $statusCode,
                    'response_data' => $responseData,
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
            ]);

            return null;
        }
    }
}
