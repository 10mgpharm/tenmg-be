<?php

namespace App\Services;

use App\Repositories\CreditCustomerDebitMandateRepository;
use App\Repositories\OfferRepository;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    public function __construct(private CreditCustomerDebitMandateRepository $creditCustomerDebitMandateRepository, private OfferRepository $offerRepository) {}

    // Handle mandate approval or mandate creation
    public function handleMandateApproval(array $payload, bool $chargeable = false): bool
    {
        // Extract relevant details from the payload
        $reference = $payload['reference'] ?? null;

        if (! $reference) {
            Log::error('Missing reference in mandate approval payload');

            return false;
        }

        $debitMandate = $this->creditCustomerDebitMandateRepository->findByReference($reference);

        if (! $debitMandate) {
            Log::error('Debit mandate not found for reference: '.$reference);

            return false;
        }

        $mandateData = [
            'business_id' => $debitMandate->business_id,
            'customer_id' => $debitMandate->customer_id,
            'authorization_code' => $payload['authorization_code'] ?? null,
            'active' => $payload['active'] ?? false,
            'last4' => $payload['last4'] ?? null,
            'channel' => $payload['channel'] ?? null,
            'card_type' => $payload['card_type'] ?? null,
            'bank' => $payload['bank'] ?? null,
            'exp_month' => $payload['exp_month'] ?? null,
            'exp_year' => $payload['exp_year'] ?? null,
            'country_code' => $payload['country_code'] ?? null,
            'brand' => $payload['brand'] ?? null,
            'reusable' => $payload['reusable'] ?? false,
            'signature' => $payload['signature'] ?? null,
            'account_name' => $payload['account_name'] ?? null,
            'integration' => $payload['integration'] ?? null,
            'domain' => $payload['domain'] ?? null,
            'chargeable' => $chargeable,
        ];
        $mandate = (bool) $this->creditCustomerDebitMandateRepository->createOrUpdateMandate($debitMandate->business_id, $debitMandate->customer_id, $mandateData);

        $this->offerRepository->updateOrCreate([
            'customer_id' => $debitMandate->customer_id,
            'business_id' => $debitMandate->business_id,
            'has_mandate' => true,
        ]);

        return $mandate;
    }

    /**
     * Verify the mandate authorization by reference.
     */
    public function verifyMandate(string $reference): array
    {
        try {
            // Make the request to Paystack API
            $response = Http::withToken(config('services.paystack.secret'))
                ->get("https://api.paystack.co/customer/authorization/verify/{$reference}");

            // Convert the response JSON
            $responseData = $response->json();

            // If the API call was successful
            if ($response->successful() && $responseData['status'] === true) {
                Log::info('Paystack Mandate Verification Successful', [
                    'reference' => $reference,
                    'data' => $responseData['data'],
                ]);

                return [
                    'status' => 'success',
                    'message' => $responseData['message'],
                    'data' => $responseData['data'],
                ];
            }

            // Log the error response if unsuccessful
            Log::error('Paystack Mandate Verification Failed', [
                'reference' => $reference,
                'response' => $responseData,
            ]);

            return [
                'status' => 'failed',
                'message' => $responseData['message'] ?? 'Mandate verification failed',
                'error_code' => $responseData['code'] ?? 'unknown',
                'metadata' => $responseData['metadata'] ?? null,
                'next_step' => $responseData['metadata']['nextStep'] ?? null,
            ];
        } catch (Exception $e) {
            // Handle any exceptions or errors during the API call
            Log::error('Paystack Mandate Verification Error', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Mandate verification error',
                'error' => $e->getMessage(),
            ];
        }
    }
}
