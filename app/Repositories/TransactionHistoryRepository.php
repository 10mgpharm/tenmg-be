<?php

namespace App\Repositories;

use App\Models\CreditScore;
use App\Models\CreditTxnHistoryEvaluation;
use Illuminate\Http\JsonResponse;

class TransactionHistoryRepository
{
    public function getTransactionHistoryEvaluationByCustomerId(int $customerId): array
    {
        return CreditTxnHistoryEvaluation::where('customer_id', $customerId)->get()->toArray();
    }

    public function paginate(array $filters, int $perPage = 15):\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = CreditTxnHistoryEvaluation::query();

        $query->when(isset($filters['search']), function ($query) use ($filters) {
            return $query
                ->where('name', 'like', "%{$filters['search']}%")
                ->orWhere('identifier', 'like', "%{$filters['search']}%")
                ->orWhere('status', 'like', "%{$filters['search']}%")
                ->orWhere('source', 'like', "%{$filters['search']}%")
                ->orWhere('file_format', 'like', "%{$filters['search']}%");
        });

        $query->when(isset($filters['status']), function ($query) use ($filters) {
            return $query->where('status', $filters['status']);
        });

        $query->when(isset($filters['vendorId']), function ($query) use ($filters) {
            return $query->where('business_id', $filters['vendorId']);
        });

        // $query->when(isset($filters['createdAtStart']) && isset($filters['createdAtEnd']), function ($query) use ($filters) {
        //     return $query->whereBetween('created_at', [$filters['createdAtStart'], $filters['createdAtEnd']]);
        // });

        $query->when(isset($filters['customerId']), function ($query) use ($filters) {
            return $query->where('customer_id', $filters['customerId']);
        });

        return $query->paginate($perPage);

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

    public function creditScoreBreakDown($txnEvaluationId):?CreditScore
    {
        return CreditScore::where('txn_evaluation_id', $txnEvaluationId)->firstOrFail();
    }
}
