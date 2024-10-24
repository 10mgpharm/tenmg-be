<?php

namespace App\Repositories;

use App\Models\CreditTxnHistoryEvaluation;

class TransactionHistoryRepository
{
    public function getTransactionHistoryEvaluationByCustomerId(int $customerId): array
    {
        return CreditTxnHistoryEvaluation::where('customer_id', $customerId)->get()->toArray();
    }

    public function createTransactionHistoryEvaluation(array $data): CreditTxnHistoryEvaluation
    {
        return CreditTxnHistoryEvaluation::create($data);
    }

    public function updateTransactionHistoryEvaluation(int $id, array $data): CreditTxnHistoryEvaluation
    {
        $evaluation = CreditTxnHistoryEvaluation::findOrFail($id);
        $evaluation->update($data);

        return $evaluation;
    }

    public function getTxnHistoryEvaluationById(int $id): ?CreditTxnHistoryEvaluation
    {
        return CreditTxnHistoryEvaluation::findOrFail($id);
    }
}
