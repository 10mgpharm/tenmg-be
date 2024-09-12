<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\ITxnHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionHistoryController extends Controller
{
    public function __construct(private ITxnHistoryService $txnHistoryService) {}

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
