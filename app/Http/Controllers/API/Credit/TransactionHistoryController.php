<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditScoreResource;
use App\Http\Resources\TxnHistoryResource;
use App\Models\CreditScore;
use App\Models\CreditTxnHistoryEvaluation;
use App\Models\FileUpload;
use App\Services\Interfaces\ITxnHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransactionHistoryController extends Controller
{
    public function __construct(private ITxnHistoryService $txnHistoryService) {}

    public function index(int $customerId): JsonResponse
    {
        $transactionHistories = $this->txnHistoryService->getTransactionHistories($customerId);

        return $this->returnJsonResponse(message: 'Transaction histories retrieved successfully.', data: $transactionHistories);
    }

    public function listAllTransactions(Request $request) : JsonResponse
    {
        $txnHistories = $this->txnHistoryService->listAllTransactions($request->all(), $request->perPage ?? 10);

        return $this->returnJsonResponse(
            data: TxnHistoryResource::collection($txnHistories)->response()->getData(true)
        );
    }

    public function uploadTransactionHistory(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,json|max:2048',
            'customerId' => 'required|exists:credit_customers,id',
        ]);

        // Call the service to handle the upload logic
        $result = $this->txnHistoryService->uploadTransactionHistory(
            file: $request->file('file'),
            customerId: $request->customerId,
        );

        // Return a success response with file and evaluation details
        return $this->returnJsonResponse(message: 'Transaction history uploaded successfully', data: $result);
    }

    public function downloadTransactionHistory($txnEvaluationId)
    {

        $evaluation = CreditTxnHistoryEvaluation::findOrFail($txnEvaluationId);
        $fileId = $evaluation->transaction_file_id;
        //get file upload entry
        $fileUpload = FileUpload::findOrFail($fileId);

        $filePath = $fileUpload->path;

        if (!Storage::exists($filePath)){
            return $this->returnJsonResponse(
                message: 'File not found',
                statusCode: Response::HTTP_NOT_FOUND,
                status:'failed'
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
        //get file upload entry
        $fileUploaded = FileUpload::findOrFail($fileId);

        if (!Storage::exists($fileUploaded->path)){
            return $this->returnJsonResponse(
                message: 'File not found',
                statusCode: Response::HTTP_NOT_FOUND,
                status:'failed'
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
        $evaluation = $this->txnHistoryService->evaluateTransactionHistory($request->transactionHistoryId);

        return $this->returnJsonResponse(message: 'Transaction history evaluated successfully.', data: $evaluation);
    }

    public function creditScoreBreakDown($txnEvaluationId):JsonResponse
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
        );

        // Return a success response with file and evaluation details
        return $this->returnJsonResponse(message: 'Transaction history uploaded and evaluated successfully', data: $result);
    }
}
