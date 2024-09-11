<?php

namespace App\Services\Interfaces;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;

interface ITxnHistoryService
{
    public function uploadTransactionHistory(File|UploadedFile|string $file, int $customerId);

    public function evaluateTransactionHistory(int $transactionHistoryId);
}
