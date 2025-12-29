<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditScoreResource;
use App\Http\Resources\CreditTransactionsResource;
use App\Http\Resources\TxnHistoryResource;
use App\Models\CreditTxnHistoryEvaluation;
use App\Models\FileUpload;
use App\Services\Credit\MonoCreditWorthinessService;
use App\Services\Interfaces\ITxnHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

            // Format response - return analysis directly if parsing was successful
            $responseData = [
                'borrower_reference' => $borrowerReference,
                'analysis' => $analysis['analysis'] ?? null,
                'mono_data' => $analysis['mono_data'] ?? null,
            ];

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
}
