<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Services\OfferService;
use Illuminate\Http\Request;

class LoanOfferController extends Controller
{
    public function __construct(private OfferService $offerService) {}

    // Create a loan offer and send to the customer
    public function createOffer(Request $request)
    {
        $request->validate([
            'applicationId' => 'required|exists:credit_applications,id',
            'offerAmount' => 'required|numeric|min:1',
        ]);

        $offer = $this->offerService->createOffer($request->applicationId, $request->offerAmount);

        return $this->returnJsonResponse(message: 'Loan offer created and sent to customer', data: $offer, statusCode: 201);
    }

    // Accept or reject the loan offer by the customer
    public function handleOfferAction(Request $request, string $offerReference)
    {
        $request->validate([
            'action' => 'required|in:accept,reject',
        ]);

        $offer = $this->offerService->acceptOrRejectOffer($offerReference, $request->action);

        return $this->returnJsonResponse(data: $offer);
    }

    // Get all offers
    public function getAllOffers()
    {
        $offers = $this->offerService->getAllOffers();

        return $this->returnJsonResponse(data: $offers);
    }

    // Get specific offer by ID
    public function getOfferById(int $id)
    {
        $offer = $this->offerService->getOfferById($id);

        return $this->returnJsonResponse(data: $offer);
    }

    // Delete an offer
    public function deleteOffer(int $id)
    {
        $this->offerService->deleteById($id);

        return $this->returnJsonResponse(message: 'Loan offer deleted');
    }

    // Enable/Disable offer
    public function toggleOfferStatus(Request $request, int $id)
    {
        $validatedData = $request->validate([
            'active' => 'required|boolean',
        ]);

        $this->offerService->toggleOfferStatus($id, $validatedData['active']);

        return $this->returnJsonResponse(message: 'Offer status updated');
    }

    // Get offers for a specific customer
    public function getOffersByCustomer(int $customerId)
    {
        $offers = $this->offerService->getOffersByCustomer($customerId);

        return response()->json(['offers' => $offers]);
    }

    public function generateMandateForCustomer(Request $request)
    {
        $request->validate([
            'offerReference' => 'required|exists:credit_offers,identifier',
        ]);

        $offer = $this->offerService->generateMandate($request->offerReference);

        return $this->returnJsonResponse(data: $offer);
    }

    public function verifyMandateForCustomer(Request $request)
    {
        $request->validate([
            'mandateReference' => 'required|exists:credit_customer_debit_mandates,reference',
        ]);

        $mandate = $this->offerService->verifyMandate($request->mandateReference);

        return $this->returnJsonResponse(data: $mandate);
    }
}
