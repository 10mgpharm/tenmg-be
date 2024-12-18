<?php

namespace App\Repositories;

use App\Models\CreditScore;
use App\Models\CreditTxnHistoryEvaluation;
use App\Models\FileUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Reader as ExcelReader;

class TransactionHistoryRepository
{
    public function getTransactionHistoryEvaluationByCustomerId(int $customerId): array
    {
        return CreditTxnHistoryEvaluation::where('customer_id', $customerId)->get()->toArray();
    }

    public function paginate(array $filters, int $perPage = 15):\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = CreditTxnHistoryEvaluation::query();

        // Join the credit_customers table
        $query->join('credit_customers', 'credit_customers.id', '=', 'credit_txn_history_evaluations.customer_id');

        // Search logic
        $query->when(isset($filters['search']), function ($query) use ($filters) {
            $searchTerm = "%{$filters['search']}%";
            return $query->where(function ($query) use ($searchTerm) {
                $query->where('credit_txn_history_evaluations.identifier', 'like', $searchTerm)
                    ->orWhere('credit_txn_history_evaluations.status', 'like', $searchTerm)
                    ->orWhere('credit_txn_history_evaluations.source', 'like', $searchTerm)
                    ->orWhere('credit_txn_history_evaluations.file_format', 'like', $searchTerm)
                    ->orWhereHas('customerRecord', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', $searchTerm);
                    });
            });
        });

        // Filter by status
        $query->when(isset($filters['status']), function ($query) use ($filters) {
            return $query->where('credit_txn_history_evaluations.status', $filters['status']);
        });

        // Filter by vendor ID
        $query->when(isset($filters['vendorId']), function ($query) use ($filters) {
            return $query->where('credit_txn_history_evaluations.business_id', $filters['vendorId']);
        });

        // Filter by customer ID
        $query->when(isset($filters['customerId']), function ($query) use ($filters) {
            return $query->where('credit_txn_history_evaluations.customer_id', $filters['customerId']);
        });

        $query->when(isset($filters['dateFrom']) && isset($filters['dateTo']), function ($query) use ($filters) {
            return $query->whereBetween('credit_txn_history_evaluations.created_at', [$filters['dateFrom'], $filters['dateTo']]);
        });

        $query->orderBy('credit_txn_history_evaluations.created_at', 'desc');

        // Prevent column conflicts by selecting specific fields
        $query->select('credit_txn_history_evaluations.*');

        // Paginate results
        return $query->paginate($perPage);

    }

    public function listAllCreditScore(array $filters, int $perPage = 15):\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {

        $query = CreditScore::query();

        // Join the credit_customers table
        $query->join('credit_customers', 'credit_customers.id', '=', 'credit_scores.customer_id');

        // Search logic
        $query->when(isset($filters['search']), function ($query) use ($filters) {
            $searchTerm = "%{$filters['search']}%";
            return $query->where(function ($query) use ($searchTerm) {
                $query->where('credit_scores.identifier', 'like', $searchTerm)
                    ->orWhere('credit_scores.source', 'like', $searchTerm)
                    ->orWhereHas('customer', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', $searchTerm);
                    });
            });
        });

        // Filter by vendor ID
        $query->when(isset($filters['vendorId']), function ($query) use ($filters) {
            return $query->where('credit_scores.business_id', $filters['vendorId']);
        });

        // Filter by customer ID
        $query->when(isset($filters['customerId']), function ($query) use ($filters) {
            return $query->where('credit_scores.customer_id', $filters['customerId']);
        });

        $query->orderBy('credit_scores.created_at', 'desc');

        // Prevent column conflicts by selecting specific fields
        $query->select('credit_scores.*');

        // Paginate results
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

    public function viewTransactionHistory(FileUpload $fileUpload): array
    {


        $filePath = $fileUpload->path;

        if($fileUpload->extension == "json"){

            $fileContent = Storage::disk(env('FILESYSTEM_DISK'))->get($filePath);
            $decodedContent = json_decode($fileContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => 'Invalid JSON content'], 400);
            }

            return $decodedContent;

        }else if($fileUpload->extension == "csv"){

            $csvContent = Storage::disk(env('FILESYSTEM_DISK'))->get($filePath);
            $csv = Reader::createFromString($csvContent);
            $csv->setHeaderOffset(0); // Use the first row as headers

            $records = [];
            foreach ($csv->getRecords() as $record) {
                $records[] = $record;
            }

            return $records;

        }else if($fileUpload->extension == "xlsx" || $fileUpload->extension == "xls"){

            $absolutePath = Storage::disk(env('FILESYSTEM_DISK'))->path($filePath);

            $sheets = Excel::toArray([], $absolutePath);


            if (!empty($sheets) && count($sheets) > 0) {
                $data = $sheets[0]; // Assuming you want the first sheet
                $headers = array_shift($data); // Use the first row as headers

                // Transform into an array of JSON
                $jsonArray = array_map(function ($row) use ($headers) {
                    return array_combine($headers, $row);
                }, $data);

                return $jsonArray;
            }

            return [];

        }else{
            return [];
        }

    }
}
