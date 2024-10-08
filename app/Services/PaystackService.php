<?php

namespace App\Services;

use App\Models\RepaymentLog;
use App\Models\RepaymentSchedule;
use App\Repositories\CreditCustomerDebitMandateRepository;
use App\Repositories\OfferRepository;
use App\Repositories\RepaymentLogRepository;
use App\Repositories\RepaymentScheduleRepository;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use \Illuminate\Http\Response as HttpResponse;

class PaystackService
{
    protected $pk_secret;
    protected $pk_url;

    public function __construct(
        private CreditCustomerDebitMandateRepository $creditCustomerDebitMandateRepository,
        private OfferRepository $offerRepository,
        private RepaymentLogRepository $repaymentLogRepository,
        private RepaymentScheduleRepository $repaymentScheduleRepository
    ) {
        $this->pk_secret = config('services.paystack.secret');
        $this->pk_url = config('services.paystack.url');
    }

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
            Log::error('Debit mandate not found for reference: ' . $reference);

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
     * Verify the transaction authorization by reference.
     */
    public function verifyPaystackTransaction(string $reference): array
    {
        try {
            // Make the request to Paystack API
            $response = Http::withToken($this->pk_secret)
                ->get($this->pk_url . "/customer/authorization/verify/" . $reference);

            // Convert the response JSON
            $responseData = $response->json();

            // If the API call was successful
            if ($response->successful() && $responseData['status'] === true) {
                Log::info('Paystack Transaction Verification Successful', [
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
            Log::error('Paystack Transaction Verification Failed', [
                'reference' => $reference,
                'response' => $responseData,
            ]);

            return [
                'status' => 'failed',
                'message' => $responseData['message'] ?? 'Transaction verification failed',
                'error_code' => $responseData['code'] ?? 'unknown',
                'metadata' => $responseData['metadata'] ?? null,
                'next_step' => $responseData['metadata']['nextStep'] ?? null,
            ];
        } catch (Exception $e) {
            // Handle any exceptions or errors during the API call
            Log::error('Paystack Transaction Verification Error', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Transaction verification error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Debit customer using Paystack.
     */
    public function debitCustomer(RepaymentSchedule $repayment, bool $isLiquidation = false): Response
    {
        $mandate = $repayment?->loan?->customer?->debitMandate;

        if (!$mandate || !$mandate->active) {
            throw new Exception('Customer does not have an active debit mandate.');
        }

        $repaymentLog = $this->initiateLogRepayment(repayment: $repayment, isLiquidation: $isLiquidation);

        $payload = [
            'authorization_code' => $mandate->authorization_code,
            'amount' => $repayment->total_amount * 100, // amount in kobo
            'email' => $repayment->loan->customer->email,
            'currency' => 'NGN',
            'reference' => $repaymentLog->reference,
        ];

        if ($isLiquidation) {
            $payload = [
                'authorization_code' => $mandate->authorization_code,
                'amount' => ($repayment->total_amount + $repayment->balance) * 100, // amount in kobo
                'email' => $repayment->loan->customer->email,
                'currency' => 'NGN',
                'reference' => $repaymentLog->reference,
            ];
        }

        $response = Http::withToken($this->pk_secret)->post(
            $this->pk_url . '/transaction/charge_authorization', $payload
        );

        $data = $response->json();

        if ($response->successful()) {

            // Update repayment status to PROCESSING
            !$isLiquidation && $repayment->update(['payment_status' => 'PROCESSING', 'payment_id' => $repaymentLog->id]);

            return $response;
        } 
        $errorMessage = 'Paystack Error: '. $data['message'] ?? 'Failed to debit customer';
        Log::error('Failed to debit customer: ' . $response->body());
        throw new Exception($errorMessage, HttpResponse::HTTP_FAILED_DEPENDENCY);
    }

    public function handleChargeSuccess(array $payload): void
    {
        $reference = $payload['data']['reference'] ?? null;

        if (! $reference) {
            Log::error('Missing reference in charge success payload');

            return;
        }

        $repaymentLog = $this->repaymentLogRepository->findByReference($reference);

        if (! $repaymentLog) {
            Log::error('Repayment log not found for reference: ' . $reference);

            return;
        }

        $this->updateLogRepayment($reference, $payload);

        // Update repayment status to PAID
        $repaymentLog->repayment->update(['payment_status' => 'PAID']);
    }

    /**
     * Log the repayment in credit_repayment_logs.
     */
    protected function initiateLogRepayment(RepaymentSchedule $repayment, bool $isLiquidation = false): RepaymentLog
    {
        $reference = '10MG-PK-' . time() . '-' . $repayment->id;

        return $this->repaymentLogRepository->logRepayment([
            'reference' => $reference,
            'business_id' => $repayment->loan->business_id,
            'customer_id' => $repayment->loan->customer_id,
            'loan_id' => $repayment->loan_id,
            'total_amount_paid' => $isLiquidation ? $repayment->total_amount + $repayment->balance : $repayment->total_amount,
            'capital_amount' => $isLiquidation ? $repayment->principal + $repayment->balance : $repayment->principal,
            'txn_status' => 'pending',
            'channel' => 'paystack',
            'channel_reference' => $reference,
        ]);
    }

    /**
     * Log the repayment in credit_repayment_logs.
     */
    protected function updateLogRepayment(string $reference, array $response): RepaymentLog
    {
        return $this->repaymentLogRepository->update(
            reference: $reference,
            data: [
                'total_amount_paid' => $response['data']['amount'] / 100, // Convert kobo to naira
                'interest_amount' => 0, // You can adjust interest calculation logic
                'penalty_fee' => 0, // Apply penalty fee logic if needed
                'txn_status' => $response['data']['status'], // Can be "success", "failed" etc.
                'channel_response' => json_encode($response['data']),
                'channel_fee' => $response['data']['fees'] / 100, // Convert kobo to naira
            ]
        );
    }

    // verify debit madate transactions every hour
    public function verifyDebitMandateTransactions()
    {
        $mandates = $this->creditCustomerDebitMandateRepository->findPendingMandate();

        foreach ($mandates as $mandate) {
            try {
                $data = $this->verifyPaystackTransaction($mandate->reference);
                if ($data['status'] != 'success') {
                    Log::info('debit mandate verificationn failed', $data);
                    continue;
                }
                $this->creditCustomerDebitMandateRepository->updateById($mandate->id, [
                    'chargeable' => true
                ]);
            } catch (\Throwable $th) {
                //throw $th;
                Log::error('Error verifying mandate', ['error' => $th->getMessage()]);
            }
        }
    }

    // verify repayment schedule transactions every hour
    public function verifyRepaymentScheduleTransactions()
    {
        $repaymentSchedules = $this->repaymentScheduleRepository->findProcessingRepayments();

        foreach ($repaymentSchedules as $repayment) {
            try {
                $data = $this->verifyPaystackTransaction($repayment->reference);
                if ($data['status'] != 'success') {
                    Log::info('debit repayment transaction verificationn failed', $data);
                    continue;
                }
                $this->repaymentLogRepository->update($repayment->reference, [
                    'payment_status' => 'PROCESSING'
                ]);
            } catch (Throwable $th) {
                //throw $th;
                Log::error('Error verifying repayment transaction', ['error' => $th->getMessage()]);
            }
        }
    }
}
