<?php

namespace App\Services;

use App\Helpers\UtilityHelper;
use App\Models\CreditOffer;
use App\Repositories\CreditCustomerDebitMandateRepository;
use App\Repositories\OfferRepository;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;

class OfferService
{
    public function __construct(
        private OfferRepository $offerRepository,
        private NotificationService $notificationService,
        private LoanApplicationService $loanApplicationService,
        private CreditCustomerDebitMandateRepository $creditCustomerDebitMandateRepository,
        private PaystackService $paystackService,
        private LoanService $loanService,
    ) {}

    public function createOffer(int $applicationId, float $offerAmount)
    {
        $application = $this->loanApplicationService->getApplicationDetails($applicationId);
        if (! $application || $application->status !== 'APPROVED') {
            throw new Exception('Loan Application not approved.', Response::HTTP_BAD_REQUEST);
        }

        // Generate the repayment breakdown using the amortization formula
        $repaymentBreakdown = UtilityHelper::generateRepaymentBreakdown(
            $offerAmount,
            $application->interest_rate,
            $application->duration_in_months
        );

        // Create offer
        $offer = $this->offerRepository->updateOrCreate([
            'customer_id' => $application->customer_id,
            'business_id' => $application->business_id,
            'application_id' => $applicationId,
            'offer_amount' => $offerAmount,
            'repayment_breakdown' => $repaymentBreakdown,
        ]);

        // Send offer letter to customer
        $this->notificationService->sendLoanOfferNotification($offer);

        return $offer;
    }

    public function acceptOrRejectOffer(string $offerReferenceId, string $action, string $reason = 'Customer rejected the offer'): CreditOffer
    {
        $offer = $this->offerRepository->findByIdentifier($offerReferenceId);
        if (! $offer) {
            throw new Exception('Offer not found.', 400);
        }

        if ($action === 'accept') {

            if (!$offer->has_mandate) {
                throw new Exception('Can not accept this offer. Customer does not have debit mandate setup yet!', 400);
            }

            // create loan repayment breakdown and create a loan
            $offer = $this->offerRepository->acceptOffer($offer->id);

            $interestData = UtilityHelper::calculateInterestAmount(
                amount: $offer->offer_amount,
                durationInMonths: $offer?->application?->duration_in_months
            );

            $interestAmount = $offer->offer_amount * $interestData['monthlyInterestRate'] * $offer?->application?->duration_in_months;

            $loanData = [
                'business_id' => $offer->business_id,
                'customer_id' => $offer->customer_id,
                'application_id' => $offer->application_id,
                'offer_id' => $offer->id,
                'capital_amount' => $offer->offer_amount,
                'interest_amount' => $interestAmount,
                'total_amount' => $offer->offer_amount + $interestAmount,
                'status' => 'PENDING_DISBURSEMENT',
            ];
            $this->loanService->createLoan($loanData, json_decode($offer->repayment_breakdown, true));
            $this->notificationService->sendOfferAcceptanceNotification($offer);
        } elseif ($action === 'reject') {
            $this->loanApplicationService->closeApplication($offer->application_id);
            $offer = $this->offerRepository->rejectOffer($offer->id, $reason);
        }

        return $offer;
    }

    public function generateMandate(string $offerReferenceId)
    {
        $offer = $this->offerRepository->findByIdentifier($offerReferenceId);
        if (! $offer) {
            throw new Exception('Offer not found.');
        }

        $mandateData = [
            'business_id' => $offer->business_id,
            'customer_id' => $offer->customer_id,
        ];

        return $this->creditCustomerDebitMandateRepository->createOrUpdateMandate($offer->business_id, $offer->customer_id, $mandateData);
    }

    public function verifyMandate(string $mandateReference): array
    {
        $mandate = $this->creditCustomerDebitMandateRepository->findByReference($mandateReference);
        if (! $mandate) {
            throw new Exception('Mandate not found.');
        }

        $response = $this->paystackService->verifyMandate($mandate->reference);
        if ($response['status'] === 'success') {
            $payload = $response['data'];

            $mandateData = [
                'authorization_code' => $payload['authorization_code'],
                'active' => $payload['active'],
                'channel' => $payload['channel'],
                'card_type' => $payload['card_type'],
                'bank' => $payload['bank'],
                'chargeable' => true,
            ];
            $this->creditCustomerDebitMandateRepository->createOrUpdateMandate($mandate->business_id, $mandate->customer_id, $mandateData);

            $this->offerRepository->updateOrCreate([
                'customer_id' => $mandate->customer_id,
                'business_id' => $mandate->business_id,
                'has_mandate' => true,
            ]);
        }

        return $response;
    }

    public function getAllOffers(): Collection
    {
        return $this->offerRepository->getAll();
    }

    public function getOfferById(int $id): ?CreditOffer
    {
        return $this->offerRepository->findById($id);
    }

    public function deleteById(int $id)
    {
        return $this->offerRepository->deleteById($id);
    }

    public function toggleOfferStatus(int $id, bool $active): CreditOffer
    {
        return $this->offerRepository->toggleStatus($id, $active);
    }

    public function getOffersByCustomer(int $customerId): Collection
    {
        return $this->offerRepository->getOffersByCustomer($customerId);
    }
}
