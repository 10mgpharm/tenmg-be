<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditScoreResource;
use App\Http\Resources\CreditTransactionsResource;
use App\Http\Resources\TxnHistoryResource;
use App\Models\Business;
use App\Models\CreditTxnHistoryEvaluation;
use App\Models\FileUpload;
use App\Models\LenderMatch;
use App\Models\MonoCustomer;
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

            // Automatically initiate GSM mandate after successful credit analysis
            $mandateUrl = null;
            if ($monoCustomerId && $lenderMatch) {
                try {
                    $monoMandateService = app(MonoMandateService::class);
                    $mandateResult = $monoMandateService->initiateMandate($lenderMatch);

                    if ($mandateResult['success']) {
                        $mandateUrl = $mandateResult['mandate_url'];
                        Log::info('GSM mandate initiated automatically', [
                            'borrower_reference' => $borrowerReference,
                            'mandate_url' => $mandateUrl,
                        ]);
                    } else {
                        Log::warning('Failed to initiate GSM mandate', [
                            'borrower_reference' => $borrowerReference,
                            'error' => $mandateResult['error'] ?? 'Unknown error',
                        ]);
                    }
                } catch (\Exception $e) {
                    // Log error but don't block credit analysis response
                    Log::error('Exception while initiating GSM mandate', [
                        'borrower_reference' => $borrowerReference,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Format response - return analysis directly if parsing was successful
            $responseData = [
                'borrower_reference' => $borrowerReference,
                'analysis' => $analysis['analysis'] ?? null,
                'mono_data' => $analysis['mono_data'] ?? null,
            ];

            // Include mandate URL if available
            if ($mandateUrl) {
                $responseData['mandate_url'] = $mandateUrl;
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
     * Test endpoint for Mono GSM mandate initiation
     * POST /api/v1/client/credit/mono-test-mandate
     * Use this endpoint to test direct debit mandate with Postman
     * Does not affect the main implementation
     *
     * Request body:
     * {
     *   "mono_customer_id": "69518e4b1e504b81ea8b27d0",
     *   "amount": 50000,
     *   "borrower_reference": "TEST_REF_001",
     *   "default_tenor": 6,
     *   "callback_url": "https://your-app.com/callback"
     * }
     */
    public function testMonoMandate(Request $request): JsonResponse
    {
        // Validate required fields
        $request->validate([
            'mono_customer_id' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'borrower_reference' => 'required|string|max:255',
            'default_tenor' => 'required|integer|min:1',
            'callback_url' => 'nullable|url',
        ]);

        try {
            $baseUrl = config('services.mono.base_url');
            $secretKey = config('services.mono.secret_key');

            if (! $secretKey) {
                return $this->returnJsonResponse(
                    message: 'Mono secret key is not configured',
                    data: ['error' => 'Mono secret key is not configured'],
                    statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
                    status: 'failed'
                );
            }

            $url = "{$baseUrl}/v2/payments/initiate";

            // Calculate dates based on tenor
            $startDate = \Carbon\Carbon::today();
            $endDate = \Carbon\Carbon::today()->addMonths($request->input('default_tenor'));

            // Generate unique reference
            $mandateReference = $request->input('borrower_reference').'_mandate_'.time();

            // Prepare mandate payload
            $mandatePayload = [
                'amount' => (int) $request->input('amount'),
                'type' => 'recurring-debit',
                'method' => 'mandate',
                'mandate_type' => 'emandate', // Global Standing Mandate
                'debit_type' => 'variable', // Variable allows flexible debit amounts
                'description' => "Loan repayment for {$request->input('borrower_reference')}",
                'reference' => $mandateReference,
                'redirect_url' => $request->input('callback_url') ?? config('app.url'),
                'customer' => [
                    'id' => $request->input('mono_customer_id'),
                ],
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'meta' => [
                    'borrower_reference' => $request->input('borrower_reference'),
                    'test_mode' => true,
                ],
            ];

            Log::info('Testing Mono GSM mandate initiation', [
                'url' => $url,
                'borrower_reference' => $request->input('borrower_reference'),
                'payload' => $mandatePayload,
            ]);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'mono-sec-key' => $secretKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post($url, $mandatePayload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            Log::info('Mono mandate test response', [
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'response_data' => $responseData,
            ]);

            if ($response->failed()) {
                $errorMessage = $responseData['message'] ?? $responseData['error'] ?? 'Failed to initiate Mono mandate';

                return $this->returnJsonResponse(
                    message: $errorMessage,
                    data: [
                        'error' => $errorMessage,
                        'status_code' => $statusCode,
                        'full_response' => $responseData,
                    ],
                    statusCode: $statusCode,
                    status: 'failed'
                );
            }

            // Extract mandate URL from response
            $mandateData = $responseData['data'] ?? $responseData;
            $monoMandateUrl = $mandateData['mono_url'] ?? null;

            if (! $monoMandateUrl) {
                return $this->returnJsonResponse(
                    message: 'Mono mandate URL not found in response',
                    data: [
                        'error' => 'Mono mandate URL not found in response',
                        'response' => $responseData,
                    ],
                    statusCode: Response::HTTP_BAD_REQUEST,
                    status: 'failed'
                );
            }

            return $this->returnJsonResponse(
                message: 'Mono GSM mandate initiated successfully',
                data: [
                    'mandate_url' => $monoMandateUrl,
                    'mandate_id' => $mandateData['mandate_id'] ?? null,
                    'reference' => $mandateReference,
                    'status_code' => $statusCode,
                ]
            );

        } catch (\Exception $e) {
            Log::error('Exception in test Mono mandate endpoint', [
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
}
