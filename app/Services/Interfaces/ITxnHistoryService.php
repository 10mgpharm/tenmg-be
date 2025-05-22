<?php

namespace App\Services\Interfaces;

use App\Models\CreditScore;
use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;

interface ITxnHistoryService
{
    public function getTransactionHistories(int $customerId): array;

    public function listAllTransactions(array $filters, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    public function listAllCreditScore(array $filters, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    public function uploadTransactionHistory(File|UploadedFile|string $file, int $customerId, User $user): array;

    public function viewTransactionHistory(FileUpload $fileUpload): array;

    public function uploadAndEvaluateTransactionHistory(File|UploadedFile|string $file, int $customerId, User $user): array;

    public function creditScoreBreakDown(int $txnEvaluationId): ?CreditScore;

    public function evaluateTransactionHistory(int $transactionHistoryId, User $user): array;

    public function getTransactionStats();

    public function getCreditTransactionHistories(array $filters, $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
}
