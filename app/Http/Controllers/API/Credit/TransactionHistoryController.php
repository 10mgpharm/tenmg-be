<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\ITxnHistoryService;
use Illuminate\Http\Request;

class TransactionHistoryController extends Controller
{
    public function __construct(private ITxnHistoryService $txnHistoryService) {}

    public function uploadTransactionHistory(Request $request)
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

    public function evaluateTransactionHistory(Request $request)
    {
        // Validate the request parameters
        $request->validate([
            'transactionHistoryId' => 'required|exists:credit_txn_history_evaluations,id',
        ]);

        // Call the service to evaluate the transaction history
        $creditScore = $this->txnHistoryService->evaluateTransactionHistory($request->transactionHistoryId);

        return $this->returnJsonResponse(message: 'Transaction history evaluated successfully.', data: $creditScore);
    }
}
