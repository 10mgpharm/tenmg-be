<?php

namespace App\Http\Controllers\API\Credit;

use App\Helpers\UtilityHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\CreditScoreResource;
use App\Http\Resources\CreditTransactionsResource;
use App\Http\Resources\TxnHistoryResource;
use App\Models\Business;
use App\Models\CreditTxnHistoryEvaluation;
use App\Models\FileUpload;
use App\Models\LenderMatch;
use App\Models\MonoCustomer;
use App\Models\MonoMandate;
use App\Services\Credit\MonoCreditWorthinessService;
use App\Services\Credit\MonoCustomerService;
use App\Services\Credit\MonoMandateService;
use App\Services\Interfaces\ITxnHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TransactionHistoryController extends Controller
{
    public function __construct(
        private ITxnHistoryService $txnHistoryService,
        private MonoCreditWorthinessService $monoCreditWorthinessService
    ) {}

    public function index(int $customerId): JsonResponse
    {
        $transactionHistories = $this->txnHistoryService->getTransactionHistories($customerId);

        return $this->returnJsonResponse(message: 'Transaction histories retrieved successfully.', data: $transactionHistories);
    }

    public function listAllTransactions(Request $request): JsonResponse
    {
        $request->merge(['vendorId' => auth()->user()->businesses()->first()->id]);

        $txnHistories = $this->txnHistoryService->listAllTransactions($request->all(), $request->perPage ?? 10);

        return $this->returnJsonResponse(
            data: TxnHistoryResource::collection($txnHistories)->response()->getData(true)
        );
    }

    public function listAllCreditScore(Request $request): JsonResponse
    {
        $request->merge(['vendorId' => auth()->user()->businesses()->first()->id]);

        $creditScoresList = $this->txnHistoryService->listAllCreditScore($request->all(), $request->perPage ?? 10);

        return $this->returnJsonResponse(
            data: CreditScoreResource::collection($creditScoresList)->response()->getData(true)
        );
    }

    public function uploadTransactionHistory(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,json|max:5048',
            'customerId' => 'required|exists:credit_customers,id',
        ]);

        // Call the service to handle the upload logic
        $result = $this->txnHistoryService->uploadTransactionHistory(
            file: $request->file('file'),
            customerId: $request->customerId,
            user: $request->user(),
        );

        // Return a success response with file and evaluation details
        return $this->returnJsonResponse(message: 'Transaction history uploaded successfully', data: $result);
    }

    public function downloadTransactionHistory($txnEvaluationId)
    {

        $evaluation = CreditTxnHistoryEvaluation::findOrFail($txnEvaluationId);
        $fileId = $evaluation->transaction_file_id;
        // get file upload entry
        $fileUpload = FileUpload::findOrFail($fileId);

        $filePath = $fileUpload->path;

        if (! Storage::exists($filePath)) {
            return $this->returnJsonResponse(
                message: 'File not found',
                statusCode: Response::HTTP_NOT_FOUND,
                status: 'failed'
            );
        }

        // Get the file's content and MIME type
        $fileContent = Storage::disk(env('FILESYSTEM_DISK'))->get($filePath);
        $mimeType = Storage::disk(env('FILESYSTEM_DISK'))->mimeType($filePath);

        // Return the file content as a response with the correct headers
        return response($fileContent, 200)->header('Content-Type', $mimeType);
    }

    public function viewTransactionHistory(Request $request)
    {
        $request->validate([
            'transactionHistoryId' => 'required|exists:credit_txn_history_evaluations,id',
        ]);

        $evaluation = CreditTxnHistoryEvaluation::findOrFail($request->transactionHistoryId);
        $fileId = $evaluation->transaction_file_id;
        // get file upload entry
        $fileUploaded = FileUpload::findOrFail($fileId);

        if (! Storage::exists($fileUploaded->path)) {
            return $this->returnJsonResponse(
                message: 'File not found',
                statusCode: Response::HTTP_NOT_FOUND,
                status: 'failed'
            );
        }

        $txnHistories = $this->txnHistoryService->viewTransactionHistory($fileUploaded);

        return $this->returnJsonResponse(message: 'Transaction history fetched successfully.', data: $txnHistories);

    }

    public function evaluateTransactionHistory(Request $request): JsonResponse
    {
        // Validate the request parameters
        $request->validate([
            'transactionHistoryId' => 'required|exists:credit_txn_history_evaluations,id',
        ]);

        // Call the service to evaluate the transaction history
        $evaluation = $this->txnHistoryService->evaluateTransactionHistory(
            transactionHistoryId: $request->transactionHistoryId,
            user: $request->user
        );

        return $this->returnJsonResponse(message: 'Transaction history evaluated successfully.', data: $evaluation);
    }

    public function creditScoreBreakDown($txnEvaluationId): JsonResponse
    {
        $creditScore = $this->txnHistoryService->creditScoreBreakDown($txnEvaluationId);

        return $this->returnJsonResponse(message: 'Credit score fetched', data: new CreditScoreResource($creditScore));
    }

    // uploadAndEvaluate
    public function uploadAndEvaluate(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,json|max:2048',
            'customerId' => 'required|exists:credit_customers,id',
        ]);

        // Call the service to handle the upload and evaluation logic
        $result = $this->txnHistoryService->uploadAndEvaluateTransactionHistory(
            file: $request->file('file'),
            customerId: $request->customerId,
            user: $request->user(),
        );

        // Return a success response with file and evaluation details
        return $this->returnJsonResponse(message: 'Transaction history uploaded and evaluated successfully', data: $result);
    }

    public function getTransactionStats()
    {
        $stats = $this->txnHistoryService->getTransactionStats();

        return $this->returnJsonResponse(message: 'Transaction stats fetched', data: $stats);
    }

    public function getCreditTransactionHistories(Request $request)
    {
        $histories = $this->txnHistoryService->getCreditTransactionHistories($request->all(), $request->perPage ?? 10);

        return $this->returnJsonResponse(message: 'Transaction histories fetched', data: CreditTransactionsResource::collection($histories)->response()->getData(true));
    }

    /**
     * Test endpoint for Mono credit history analysis using Gemini AI
     */
    public function testMonoCreditWorthiness(Request $request): JsonResponse
    {
        // Validate BVN and borrower_reference are provided
        $request->validate([
            'bvn' => 'required|string|size:11',
            'borrower_reference' => 'required|string|max:255',
        ]);

        $bvn = $request->input('bvn');
        $borrowerReference = $request->input('borrower_reference');
        // Provider comes from config/env, not from user input
        $provider = config('services.mono.default_provider', 'crc');

        // Fetch credit history from Mono API
        $apiResponse = $this->monoCreditWorthinessService->fetchCreditHistory($bvn, $provider);

        if (! $apiResponse['success']) {
            // Return detailed error information
            $errorData = [
                'error' => $apiResponse['error'] ?? 'Failed to fetch credit history from Mono API',
                'status_code' => $apiResponse['status_code'] ?? null,
                'response' => $apiResponse['response'] ?? null,
                'raw_response' => $apiResponse['raw_response'] ?? null,
                'exception_type' => $apiResponse['exception_type'] ?? null,
            ];

            // Remove null values for cleaner response
            $errorData = array_filter($errorData, fn ($value) => $value !== null);

            return $this->returnJsonResponse(
                message: $apiResponse['error'] ?? 'Failed to fetch credit history from Mono API',
                data: $errorData,
                statusCode: $apiResponse['status_code'] ?? Response::HTTP_INTERNAL_SERVER_ERROR,
                status: 'failed'
            );
        }

        // Format response to match expected structure
        $monoData = [
            'status' => 'successful',
            'message' => 'Report Fetched Successfully',
            'timestamp' => now()->toIso8601String(),
            'data' => $apiResponse['data'],
        ];

        // Extract profile data and create/get Mono customer
        $monoCustomerService = app(MonoCustomerService::class);
        $profileData = $apiResponse['data']['profile'] ?? [];

        // Get vendor business from lender_matches table using borrower_reference
        $vendorBusiness = null;
        $lenderMatch = LenderMatch::where('borrower_reference', $borrowerReference)->first();
        if ($lenderMatch && $lenderMatch->vendor_business_id) {
            $vendorBusiness = Business::find($lenderMatch->vendor_business_id);
        }

        // Extract first_name and last_name from Mono credit history response
        // Mono returns: "fullName": "Samuel Olamide", "firstName": null, "lastName": null
        $fullName = $profileData['fullName'] ?? $profileData['full_name'] ?? null;
        $firstName = $profileData['firstName'] ?? $profileData['first_name'] ?? null;
        $lastName = $profileData['lastName'] ?? $profileData['last_name'] ?? null;

        // If first_name or last_name not available, split fullName
        if ((! $firstName || ! $lastName) && ! empty($fullName)) {
            $fullName = trim($fullName);
            if ($fullName) {
                $nameParts = explode(' ', $fullName, 2);
                if (! $firstName) {
                    $firstName = $nameParts[0] ?? null;
                }
                if (! $lastName && isset($nameParts[1])) {
                    $lastName = $nameParts[1];
                }
            }
        }

        // Extract customer fields from profile - ensure strings, not arrays
        $email = $profileData['email'] ?? null;
        if (is_array($email)) {
            $email = $email[0] ?? null;
        }
        if (! $email) {
            $emailAddress = $profileData['email_address'] ?? null;
            if (is_array($emailAddress)) {
                $email = $emailAddress[0] ?? null;
            } else {
                $email = $emailAddress;
            }
        }
        if (! $email && isset($profileData['email_addresses']) && is_array($profileData['email_addresses'])) {
            $email = $profileData['email_addresses'][0] ?? null;
        }

        $phone = $profileData['phone'] ?? null;
        if (is_array($phone)) {
            $phone = $phone[0] ?? null;
        }
        if (! $phone) {
            $phoneNumber = $profileData['phone_number'] ?? null;
            if (is_array($phoneNumber)) {
                $phone = $phoneNumber[0] ?? null;
            } else {
                $phone = $phoneNumber;
            }
        }
        if (! $phone && isset($profileData['phone_numbers']) && is_array($profileData['phone_numbers'])) {
            $phone = $profileData['phone_numbers'][0] ?? null;
        }

        $address = $profileData['address'] ?? null;
        if (! $address && isset($profileData['address_history'][0])) {
            $addressHistory = $profileData['address_history'][0];
            if (is_array($addressHistory)) {
                $address = $addressHistory['address'] ?? ($addressHistory[0] ?? null);
            } else {
                $address = $addressHistory;
            }
        }

        $customerProfileData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $fullName ?? ($profileData['fullName'] ?? $profileData['full_name'] ?? null),
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'bvn' => $bvn, // Include BVN for Mono API
        ];

        // Create or get Mono customer (don't fail if this fails)
        $monoCustomerId = null;
        try {
            $monoCustomerId = $monoCustomerService->createOrGetMonoCustomer(
                $customerProfileData,
                $bvn,
                $vendorBusiness
            );

            // Update lender match with Mono customer ID
            if ($monoCustomerId) {
                $monoCustomer = MonoCustomer::where('mono_customer_id', $monoCustomerId)->first();
                if ($monoCustomer) {
                    LenderMatch::where('borrower_reference', $borrowerReference)
                        ->update(['mono_customer_id' => $monoCustomer->id]);
                    // Refresh lender match to get updated mono_customer_id
                    $lenderMatch->refresh();
                }
            }
        } catch (\Exception $e) {
            // Log error but don't block credit analysis
            Log::warning('Failed to create/get Mono customer', [
                'error' => $e->getMessage(),
                'borrower_reference' => $borrowerReference,
            ]);
        }

        try {
            // Analyze credit history data with Gemini AI
            $analysis = $this->monoCreditWorthinessService->analyzeMonoCreditWorthiness($monoData, $borrowerReference);

            if (! $analysis['success']) {
                return $this->returnJsonResponse(
                    message: $analysis['message'] ?? 'Failed to analyze credit history',
                    data: $analysis,
                    statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
                    status: 'failed'
                );
            }

            // Build repayment preview and normalize AI analysis
            $analysisData = $analysis['analysis'] ?? null;
            $monoData = $result = $analysis['mono_data'] ?? [];
            $repaymentCalculation = null;

            if ($analysisData && $lenderMatch) {
                // Derive deterministic summary from mono_data for guardrails
                $summary = $monoData['credit_history_summary'] ?? [];
                $repaymentStatus = $summary['repayment_schedule_status'] ?? [];
                $totalLoans = (int) ($summary['total_loans'] ?? 0);
                $activeLoans = (int) ($summary['active_loans'] ?? 0);
                $nonPerforming = (int) ($summary['non_performing_loans'] ?? 0);
                $performanceRatio = (float) ($summary['performance_ratio'] ?? 0);
                $overdueCount = (int) ($repaymentStatus['overdue'] ?? 0);

                $isCleanProfile = $totalLoans >= 1
                    && $activeLoans === 0
                    && $nonPerforming === 0
                    && $performanceRatio >= 99
                    && $overdueCount === 0;

                // Normalize naming (camelCase and snake_case) for key fields
                $loanRecommendationRaw = $analysisData['loanRecommendation']
                    ?? $analysisData['loan_recommendation']
                    ?? [];

                $requestedAmount = (float) (
                    $analysisData['requestedAmount']
                    ?? $analysisData['requested_amount']
                    ?? $analysis['requested_amount']
                    ?? $lenderMatch->amount
                    ?? 0
                );

                $recommendedAmount = (float) (
                    $analysisData['recommendedLoanAmount']
                    ?? $analysisData['recommended_loan_amount']
                    ?? $loanRecommendationRaw['approvedAmount']
                    ?? $loanRecommendationRaw['approved_amount']
                    ?? 0
                );

                $creditCategory = $analysisData['creditCategory']
                    ?? $analysisData['credit_category']
                    ?? null;

                // Apply clean-profile guardrails: always A and full amount if history is perfect
                if ($isCleanProfile && $requestedAmount > 0) {
                    $creditCategory = 'A';
                    $analysisData['creditCategory'] = 'A';
                    $analysisData['credit_category'] = 'A';

                    $stableScore = 90.0;
                    $analysisData['creditScorePercentage'] = $stableScore;
                    $analysisData['credit_score_percentage'] = $stableScore;

                    $recommendedAmount = $requestedAmount;
                    $analysisData['recommendedLoanAmount'] = $recommendedAmount;
                    $analysisData['recommended_loan_amount'] = $recommendedAmount;

                    $analysisData['eligibleForLoan'] = true;
                    $analysisData['eligible_for_loan'] = true;

                    // Clear noisy conditions for clean profiles
                    if (isset($analysisData['loanRecommendation']) && is_array($analysisData['loanRecommendation'])) {
                        $analysisData['loanRecommendation']['approved'] = true;
                        $analysisData['loanRecommendation']['approvedAmount'] = $recommendedAmount;
                        $analysisData['loanRecommendation']['approved_amount'] = $recommendedAmount;
                        $analysisData['loanRecommendation']['conditions'] = [];
                    }
                    if (isset($analysisData['loan_recommendation']) && is_array($analysisData['loan_recommendation'])) {
                        $analysisData['loan_recommendation']['approved'] = true;
                        $analysisData['loan_recommendation']['approved_amount'] = $recommendedAmount;
                        $analysisData['loan_recommendation']['conditions'] = [];
                    }
                }

                // Full approval if category A and recommended >= requested
                $fullApproval = $creditCategory === 'A' && $recommendedAmount >= $requestedAmount && $requestedAmount > 0;

                // Decide amount to use for repayment preview
                $approvedAmount = $fullApproval
                    ? $requestedAmount
                    : ($recommendedAmount > 0 ? $recommendedAmount : $requestedAmount);

                // Canonical tenor: vendor-selected tenor from LenderMatch, capped to 1–4 months
                $defaultTenor = (int) ($lenderMatch->default_tenor ?? 1);
                $tenorInMonths = max(1, min($defaultTenor, 4));

                if ($approvedAmount > 0 && $tenorInMonths > 0) {
                    // Derive effective (lender + 10mg) interest rate with 15% total cap
                    $lenderMatch->loadMissing('lender.lenderSetting');
                    $baseRate = (float) ($lenderMatch->lender->lenderSetting->rate ?? 0);
                    $rates = UtilityHelper::getEffectiveInterestRates($baseRate, null);
                    $effectiveRate = $rates['effective_rate'];

                    // Simple interest calculation (same pattern as LoanPreferenceService)
                    $interestAmount = $approvedAmount * ($effectiveRate / 100);
                    $totalRepayable = $approvedAmount + $interestAmount;
                    $monthlyRepayment = $totalRepayable / $tenorInMonths;

                    $schedule = [];
                    $today = now()->startOfDay();
                    $remaining = $totalRepayable;

                    for ($i = 1; $i <= $tenorInMonths; $i++) {
                        // For the last installment, adjust for rounding so total matches exactly
                        if ($i === $tenorInMonths) {
                            $amountThisInstallment = round($remaining, 2);
                        } else {
                            $amountThisInstallment = round($monthlyRepayment, 2);
                            $remaining -= $amountThisInstallment;
                        }

                        $schedule[] = [
                            'installment_number' => $i,
                            'due_date' => $today->copy()->addMonths($i)->toDateString(),
                            'amount' => $amountThisInstallment,
                        ];
                    }

                    $repaymentCalculation = [
                        'principal' => (int) $approvedAmount,
                        'tenor_interest_rate' => $effectiveRate,
                        'tenor_in_months' => $tenorInMonths,
                        'interest_amount' => round($interestAmount, 2),
                        'total_repayable' => round($totalRepayable, 2),
                        'monthly_repayment' => round($monthlyRepayment, 2),
                        'repayment_schedule' => $schedule,
                        'summary_text' => sprintf(
                            'For a recommended loan of ₦%s spread over %d months at %.2f%%, your total repayment will be ₦%s and your estimated monthly payment will be ₦%s.',
                            number_format($approvedAmount),
                            $tenorInMonths,
                            $effectiveRate,
                            number_format(round($totalRepayable, 2)),
                            number_format(round($monthlyRepayment, 2))
                        ),
                    ];
                }

                // Adjust loanRecommendation visibility:
                // - If full approval (A and full amount), hide noisy recommendation block
                // - Otherwise keep whatever AI returned
                if ($fullApproval) {
                    unset($analysisData['loanRecommendation'], $analysisData['loan_recommendation']);
                }
            }

            // Format response - return analysis directly if parsing was successful
            $responseData = [
                'borrower_reference' => $borrowerReference,
                'callback_url' => $lenderMatch->callback_url ?? null,
                'analysis' => $analysisData,
                'mono_data' => $analysis['mono_data'] ?? null,
            ];

            if ($repaymentCalculation) {
                $responseData['analysis']['repayment_calculation'] = $repaymentCalculation;
            }

            // Only include raw_response if parsing failed or for debugging
            if (isset($analysis['parse_error']) || ! isset($analysis['analysis'])) {
                $responseData['raw_response'] = $analysis['raw_response'] ?? null;
                $responseData['parse_error'] = $analysis['parse_error'] ?? null;
            }

            return $this->returnJsonResponse(
                message: 'Mono credit history analyzed successfully',
                data: $responseData
            );

        } catch (\Exception $e) {
            return $this->returnJsonResponse(
                message: 'Error processing Mono credit history analysis',
                data: ['error' => $e->getMessage()],
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
                status: 'failed'
            );
        }
    }

    /**
     * Initiate Mono GSM mandate for a borrower
     * POST /api/v1/client/credit/initiate-mandate
     * Call this endpoint after credit check to initiate the mandate
     *
     * Request body:
     * {
     *   "borrower_reference": "USER_125",
     *   "customer_data": {  // Optional: only needed if MonoCustomer doesn't exist
     *     "bvn": "12345678901",
     *     "first_name": "John",
     *     "last_name": "Doe",
     *     "email": "john@example.com",
     *     "phone": "08012345678",
     *     "address": "123 Main Street, Lagos"
     *   }
     * }
     */
    public function initiateMandate(Request $request): JsonResponse
    {
        // Validate required fields
        $request->validate([
            'borrower_reference' => 'required|string|max:255',
            'customer_data' => 'sometimes|array',
            'customer_data.bvn' => 'required_with:customer_data|string|size:11',
            'customer_data.first_name' => 'required_with:customer_data|string|max:255',
            'customer_data.last_name' => 'required_with:customer_data|string|max:255',
            'customer_data.email' => 'required_with:customer_data|email|max:255',
            'customer_data.phone' => 'required_with:customer_data|string|max:20',
            'customer_data.address' => 'required_with:customer_data|string|max:255',
        ]);

        $borrowerReference = $request->input('borrower_reference');
        $customerData = $request->input('customer_data');

        try {
            // Find lender match by borrower_reference
            $lenderMatch = LenderMatch::where('borrower_reference', $borrowerReference)->first();

            if (! $lenderMatch) {
                return $this->returnJsonResponse(
                    message: 'Lender match not found for the given borrower reference',
                    data: ['error' => 'Lender match not found'],
                    statusCode: Response::HTTP_NOT_FOUND,
                    status: 'failed'
                );
            }

            // Initiate mandate using the service (will create MonoCustomer if needed)
            $monoMandateService = app(MonoMandateService::class);
            $result = $monoMandateService->initiateMandate($lenderMatch, $customerData);

            if (! $result['success']) {
                return $this->returnJsonResponse(
                    message: $result['error'] ?? 'Failed to initiate Mono mandate',
                    data: $result,
                    statusCode: $result['status_code'] ?? Response::HTTP_BAD_REQUEST,
                    status: 'failed'
                );
            }

            // If mock response exists (business not active), return full mock data
            if (isset($result['mock_response'])) {
                return $this->returnJsonResponse(
                    message: 'Payment Initiated Successfully',
                    data: array_merge($result['mock_response'], [
                        'mandate_id' => $result['mandate_id'] ?? null,
                    ])
                );
            }

            return $this->returnJsonResponse(
                message: 'Payment Initiated Successfully',
                data: [
                    'mono_url' => $result['mandate_url'],
                    'mandate_id' => $result['mandate_id'] ?? null,
                    'status_code' => $result['status_code'] ?? 200,
                ]
            );

        } catch (\Exception $e) {
            Log::error('Exception while initiating Mono mandate', [
                'borrower_reference' => $borrowerReference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->returnJsonResponse(
                message: 'Error initiating Mono mandate',
                data: ['error' => $e->getMessage()],
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
                status: 'failed'
            );
        }
    }

    /**
     * Verify Mono mandate status
     * GET /api/v1/client/credit/verify-mandate/{identifier}
     *
     * The identifier can be either mandate_id or reference
     */
    public function verifyMandate(string $identifier): JsonResponse
    {
        try {
            // Try to find mandate by mandate_id first, then by reference
            $mandate = MonoMandate::where('mandate_id', $identifier)
                ->orWhere('reference', $identifier)
                ->first();

            if (! $mandate) {
                return $this->returnJsonResponse(
                    message: 'Mandate not found',
                    data: ['error' => 'Mandate not found with the provided mandate_id or reference'],
                    statusCode: Response::HTTP_NOT_FOUND,
                    status: 'failed'
                );
            }

            return $this->returnJsonResponse(
                message: 'Mandate status retrieved successfully',
                data: [
                    'mandate_id' => $mandate->mandate_id,
                    'reference' => $mandate->reference,
                    'status' => $mandate->status,
                    'callback_url' => $mandate->meta['vendor_callback_url'] ?? $mandate->redirect_url ?? null,
                    'amount' => $mandate->amount,
                    'currency' => $mandate->currency,
                    'start_date' => $mandate->start_date->format('Y-m-d'),
                    'end_date' => $mandate->end_date->format('Y-m-d'),
                    'is_mock' => $mandate->is_mock,
                    'created_at' => $mandate->created_at->toIso8601String(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Exception while verifying Mono mandate', [
                'identifier' => $identifier,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->returnJsonResponse(
                message: 'Failed to verify mandate',
                data: ['error' => $e->getMessage()],
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
                status: 'failed'
            );
        }
    }

    /**
     * Update Mono mandate status
     * PUT/PATCH /api/v1/client/credit/update-mandate-status/{identifier}
     *
     * The identifier can be either mandate_id or reference
     *
     * Request body:
     * {
     *   "status": "approved" // pending, approved, rejected, cancelled, expired
     * }
     */
    public function updateMandateStatus(Request $request, string $identifier): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:pending,approved,rejected,cancelled,expired',
            ]);

            // Try to find mandate by mandate_id first, then by reference
            $mandate = MonoMandate::where('mandate_id', $identifier)
                ->orWhere('reference', $identifier)
                ->first();

            if (! $mandate) {
                return $this->returnJsonResponse(
                    message: 'Mandate not found',
                    data: ['error' => 'Mandate not found with the provided mandate_id or reference'],
                    statusCode: Response::HTTP_NOT_FOUND,
                    status: 'failed'
                );
            }

            $oldStatus = $mandate->status;
            $mandate->update([
                'status' => $validated['status'],
            ]);

            Log::info('Mono mandate status updated', [
                'identifier' => $identifier,
                'mandate_id' => $mandate->mandate_id,
                'reference' => $mandate->reference,
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
            ]);

            return $this->returnJsonResponse(
                message: 'Mandate status updated successfully',
                data: [
                    'mandate_id' => $mandate->mandate_id,
                    'reference' => $mandate->reference,
                    'status' => $mandate->status,
                    'previous_status' => $oldStatus,
                    'updated_at' => $mandate->updated_at->toIso8601String(),
                ]
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->returnJsonResponse(
                message: 'Validation failed',
                data: ['errors' => $e->errors()],
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
                status: 'failed'
            );
        } catch (\Exception $e) {
            Log::error('Exception while updating Mono mandate status', [
                'identifier' => $identifier,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->returnJsonResponse(
                message: 'Failed to update mandate status',
                data: ['error' => $e->getMessage()],
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
                status: 'failed'
            );
        }
    }

    /**
     * Update lender match status and related mandate status
     * PUT/PATCH /api/v1/client/credit/update-match-status/{borrower_reference}
     *
     * Request body:
     * {
     *   "status": "approved", // matched, approved, rejected, cancelled, expired
     *   "businessname": "Business Name" // optional
     * }
     *
     * When match status is updated, related mandate status is also updated.
     */
    public function updateMatchStatus(Request $request, string $borrowerReference): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:matched,approved,rejected,cancelled,expired',
                'businessname' => 'nullable|string|max:255',
            ]);

            $lenderMatch = LenderMatch::where('borrower_reference', $borrowerReference)->first();

            if (! $lenderMatch) {
                return $this->returnJsonResponse(
                    message: 'Lender match not found',
                    data: ['error' => 'Lender match not found'],
                    statusCode: Response::HTTP_NOT_FOUND,
                    status: 'failed'
                );
            }

            $oldStatus = $lenderMatch->status;

            $updateData = [
                'status' => $validated['status'],
            ];

            // Add businessname if provided
            if (isset($validated['businessname'])) {
                $updateData['businessname'] = $validated['businessname'];
            }

            $lenderMatch->update($updateData);

            // Map match status to mandate status
            $mandateStatusMap = [
                'matched' => 'pending',
                'approved' => 'approved',
                'rejected' => 'rejected',
                'cancelled' => 'cancelled',
                'expired' => 'expired',
            ];

            $mandateStatus = $mandateStatusMap[$validated['status']] ?? 'pending';

            // Update all related mandates
            $updatedMandates = $lenderMatch->monoMandates()->update([
                'status' => $mandateStatus,
            ]);

            Log::info('Lender match status updated with related mandates', [
                'borrower_reference' => $borrowerReference,
                'lender_match_id' => $lenderMatch->id,
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
                'businessname' => $validated['businessname'] ?? null,
                'mandate_status' => $mandateStatus,
                'mandates_updated' => $updatedMandates,
            ]);

            return $this->returnJsonResponse(
                message: 'Match status updated successfully',
                data: [
                    'borrower_reference' => $lenderMatch->borrower_reference,
                    'lender_match_id' => $lenderMatch->id,
                    'status' => $lenderMatch->status,
                    'businessname' => $lenderMatch->businessname,
                    'previous_status' => $oldStatus,
                    'mandate_status' => $mandateStatus,
                    'mandates_updated' => $updatedMandates,
                    'updated_at' => $lenderMatch->updated_at->toIso8601String(),
                ]
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->returnJsonResponse(
                message: 'Validation failed',
                data: ['errors' => $e->errors()],
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
                status: 'failed'
            );
        } catch (\Exception $e) {
            Log::error('Exception while updating lender match status', [
                'borrower_reference' => $borrowerReference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->returnJsonResponse(
                message: 'Failed to update match status',
                data: ['error' => $e->getMessage()],
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
                status: 'failed'
            );
        }
    }
}
