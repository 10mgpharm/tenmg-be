<?php

namespace App\Services\Credit;

use App\Models\LenderMatch;
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
     */
    public function initiateMandate(LenderMatch $lenderMatch): array
    {
        try {
            // Ensure we have Mono customer ID
            if (! $lenderMatch->mono_customer_id) {
                $monoCustomer = $lenderMatch->monoCustomer;
                if (! $monoCustomer || ! $monoCustomer->mono_customer_id) {
                    return [
                        'success' => false,
                        'error' => 'Mono customer ID is required to initiate mandate',
                    ];
                }
            } else {
                $monoCustomer = $lenderMatch->monoCustomer;
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

            // Generate unique reference
            $mandateReference = $lenderMatch->borrower_reference.'_mandate_'.time();

            // Prepare mandate payload
            $mandatePayload = [
                'amount' => (int) $lenderMatch->amount,
                'type' => 'recurring-debit',
                'method' => 'mandate',
                'mandate_type' => 'gsm', // Global Standing Mandate
                'debit_type' => 'variable', // Variable allows flexible debit amounts
                'description' => "Loan repayment for {$lenderMatch->borrower_reference}",
                'reference' => $mandateReference,
                'redirect_url' => $lenderMatch->callback_url ?? config('app.url'),
                'customer' => [
                    'id' => $monoCustomer->mono_customer_id,
                ],
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'meta' => [
                    'borrower_reference' => $lenderMatch->borrower_reference,
                    'lender_match_id' => $lenderMatch->id,
                ],
            ];

            Log::info('Initiating Mono GSM mandate', [
                'url' => $url,
                'lender_match_id' => $lenderMatch->id,
                'borrower_reference' => $lenderMatch->borrower_reference,
                'payload' => array_merge($mandatePayload, ['customer' => ['id' => $monoCustomer->mono_customer_id]]),
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
                'mono_customer_id' => $monoCustomer->id,
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
}
