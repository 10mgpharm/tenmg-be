<?php

namespace App\Services\Interfaces;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;

interface ITxnHistoryService
{
    public function getTransactionHistories(int $customerId): array;

    public function listAllTransactions(array $filters, int $perPage):\Illuminate\Contracts\Pagination\LengthAwarePaginator;

    public function uploadTransactionHistory(File|UploadedFile|string $file, int $customerId): array;

    public function uploadAndEvaluateTransactionHistory(File|UploadedFile|string $file, int $customerId): array;

    public function evaluateTransactionHistory(int $transactionHistoryId): array;
}
