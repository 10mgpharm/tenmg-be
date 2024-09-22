<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Services\LoanApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoanApplicationController extends Controller
{
    public function __construct(private LoanApplicationService $loanApplicationService)
    {
        Log::info('Request reached Controller: '.get_class($this));
    }

    // Submit New Application via Dashboard
    public function store(Request $request)
    {
        $request->validate([
            'customerId' => 'required_without:reference|exists:credit_customers,id',
            'requestedAmount' => 'required|numeric|min:1',
            'durationInMonths' => 'required|integer|min:1|max:12',
            'reference' => 'nullable|exists:credit_applications,identifier',
        ]);

        $application = isset($request->reference) ? $this->loanApplicationService->updateApplication($request->all()) : $this->loanApplicationService->createApplication($request->all());

        return $this->returnJsonResponse('Loan application submitted successfully', $application, 201);
    }

    // Submit Loan Application from E-commerce Site
    public function applyFromEcommerce(Request $request)
    {
        $request->validate([
            'customerId' => 'required|exists:credit_customers,id',
        ]);

        // Call service to generate the application link
        $referenceLink = $this->loanApplicationService->createEcommerceApplication([
            'vendorId' => $request->headers->get('x-vendor-key'),
            'vendorSecret' => $request->headers->get('x-vendor-secret'),
            'customerId' => $request->customerId,
        ]);

        return $this->returnJsonResponse('Application link generated', ['link' => $referenceLink]);
    }

    // Retrieve Vendor Customizations
    public function getCustomisations(Request $request)
    {
        $customizations = $this->loanApplicationService->getVendorCustomisations();

        return $this->returnJsonResponse(data: $customizations);
    }

    public function getLoanApplicationByReference(string $reference)
    {
        $application = $this->loanApplicationService->getLoanApplicationByReference($reference);

        return $this->returnJsonResponse(data: $application);
    }

    public function destroy($id)
    {
        $this->loanApplicationService->deleteApplication($id);

        return $this->returnJsonResponse('Application deleted successfully');
    }

    public function index()
    {
        $applications = $this->loanApplicationService->getLoanApplications();

        return $this->returnJsonResponse(data: $applications);
    }

    // Filter Loan Applications
    public function filter(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:pending,approved,rejected',
            'search' => 'nullable|string',
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date',
            'businessId' => 'nullable|exists:businesses,id',
        ]);

        $applications = $this->loanApplicationService->getLoanApplicationsByFilter($request->all());

        return $this->returnJsonResponse(data: $applications);
    }

    // View Loan Application Details
    public function show(int $id): JsonResponse
    {
        $application = $this->loanApplicationService->getApplicationDetails($id);

        return $this->returnJsonResponse(data: $application);
    }

    // Approve/Reject Loan Application (10mg Admins Only)
    public function review(Request $request, int $applicationId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:approve,reject',
            'offerAmount' => 'required|numeric|min:1',
        ]);

        // Assuming the admin ID is available in the request
        $this->loanApplicationService->reviewApplication($applicationId, $request->status, $request->offerAmount);

        return $this->returnJsonResponse(message: 'Application reviewed successfully');
    }

    // View All Applications for a Specific Customer
    public function getCustomerApplications(int $customerId)
    {
        $applications = $this->loanApplicationService->getApplicationsByCustomer($customerId);

        return $this->returnJsonResponse(data: $applications);
    }
}
