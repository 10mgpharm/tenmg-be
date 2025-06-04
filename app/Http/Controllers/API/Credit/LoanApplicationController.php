<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoadApplicationResource;
use App\Models\LoanApplication;
use App\Repositories\FincraMandateRepository;
use App\Services\LoanApplicationService;
use App\Services\OfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoanApplicationController extends Controller
{
    public function __construct(private LoanApplicationService $loanApplicationService, private OfferService $offerService, private FincraMandateRepository $fincraMandateRepository) {}

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

    // Submit Loan Application link
    public function sendApplicationLink(Request $request)
    {
        $request->validate([
            'customerId' => 'required|exists:credit_customers,id',
            'requestedAmount' => 'required|numeric|min:1',
        ]);

        // Call service to generate the application link
        $referenceLink = $this->loanApplicationService->sendApplicationLink([
            'customerId' => $request->customerId,
            'requestedAmount' => $request->requestedAmount,
        ]);

        return $this->returnJsonResponse('Application link generated', ['link' => $referenceLink]);
    }

    public function verifyApplicationLink($reference)
    {
        //check if load application exist with this id
        $application = LoanApplication::where("identifier", $reference)->first();

        if(!$application){
            return $this->returnJsonResponse(message:"Application not found", status:400);
        }

        $data = $this->loanApplicationService->verifyApplicationLink($reference);

        return $this->returnJsonResponse(data: $data);

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

    public function getLoanApplicationByReferenceResourced(string $reference)
    {
        $application = $this->loanApplicationService->getLoanApplicationByReference($reference);

        return $this->returnJsonResponse(data: new LoadApplicationResource($application));
    }

    public function destroy($id)
    {
        $this->loanApplicationService->deleteApplication($id);

        return $this->returnJsonResponse('Application deleted successfully');
    }

    public function index(Request $request)
    {
        $applications = $this->loanApplicationService->getLoanApplications($request->all(), $request->perPage ?? 10);

        return $this->returnJsonResponse(data: LoadApplicationResource::collection($applications)->response()->getData(true));
    }

    public function getLoanApplicationLenders(Request $request)
    {
        $applications = $this->loanApplicationService->getLoanApplicationLenders($request->all(), $request->perPage ?? 10);

        return $this->returnJsonResponse(data: LoadApplicationResource::collection($applications)->response()->getData(true));
    }

    public function getLoanApplicationStats()
    {
        $stats = $this->loanApplicationService->getLoanApplicationStats();

        return $this->returnJsonResponse(data: $stats);
    }

    public function approveLoanApplicationManually(Request $request)
    {
        $request->validate([
            'applicationId' => 'required|exists:credit_applications,identifier',
            'action' => 'required|string|in:approve,decline',
        ]);

        $application = $this->loanApplicationService->approveLoanApplicationManually($request);

        return $this->returnJsonResponse(data: $application, message: 'Application approved successfully');
    }

    // Filter Loan Applications
    public function filter(Request $request):JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:pending,approved,rejected',
            'search' => 'nullable|string',
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date',
            'businessId' => 'nullable|exists:businesses,id',
        ]);

        $applications = $this->loanApplicationService->getLoanApplicationsByFilter($request->all(), $request->perPage ?? 10);

        return $this->returnJsonResponse(data: LoadApplicationResource::collection($applications)->response()->getData(true));
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
        $application = $this->loanApplicationService->reviewApplication($applicationId, $request->status, $request->offerAmount);

        if ($application->status === 'APPROVED') {
            // Create an offer for the approved application
            $offerAmount = $request->offerAmount;
            $this->offerService->createOffer($applicationId, $offerAmount);
        }

        return $this->returnJsonResponse(message: 'Application reviewed successfully');
    }

    // View All Applications for a Specific Customer
    public function getCustomerApplications(int $customerId)
    {
        $applications = $this->loanApplicationService->getApplicationsByCustomer($customerId);

        return $this->returnJsonResponse(data: $applications);
    }


    public function generateMandateForCustomerClient(Request $request)
    {
        $request->validate([
            'duration' => 'required|in:3,6,9,12',
            'loanApplicationIdentifier' =>'required|exists:credit_applications,identifier',
            'bank' => 'required|string',
            'customerAccountName' => 'required|string',
            'customerAccountNumber' => 'required|string',
            'customerBankCode' => 'required|string',
        ]);

        $mandate = $this->loanApplicationService->generateMandateForCustomerClient($request);

        return $this->returnJsonResponse(data: $mandate);
    }

    public function verifyMandateStatus($reference)
    {
        $mandateStatus = $this->loanApplicationService->verifyMandateStatus($reference);
        return $this->returnJsonResponse(data: $mandateStatus, message: 'Mandate status retrieved successfully');
    }

    public function verifyLoanApplicationStatus($reference)
    {
        $mandateStatus = $this->loanApplicationService->verifyLoanApplicationStatus($reference);
        return $this->returnJsonResponse(data: $mandateStatus, message: 'Application status retrieved successfully');
    }

    public function completeLoadApplication($applicationId)
    {
        return $this->fincraMandateRepository->completeLoanApplication($applicationId);
    }

    public function debitCustomerMandate($applicationId)
    {
        return $this->fincraMandateRepository->debitCustomerMandate($applicationId);
    }

    public function getApplicationStatus(Request $request)
    {
        $applicationStatus = $this->loanApplicationService->getApplicationStatus($request->reference);

        return $this->returnJsonResponse(
            data: $applicationStatus,
            message: 'Application status retrieved successfully'
        );
    }

    public function cancelApplication($reference)
    {
        $this->loanApplicationService->cancelApplication($reference);

        return $this->returnJsonResponse(
            message: 'Application cancelled successfully'
        );
    }
}
