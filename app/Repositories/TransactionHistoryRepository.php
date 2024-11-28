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
