<?php

namespace App\Repositories;

use App\Models\RepaymentLog;
use App\Models\RepaymentSchedule;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class RepaymentLogRepository
{
    /**
     * Insert a new repayment log.
     */
    public function logRepayment(array $data): RepaymentLog
    {
        return RepaymentLog::create($data);
    }

    public function update(string $reference, array $data): RepaymentLog
    {
        $repaymentLog = RepaymentLog::whereReference($reference)->first();
        if (!$repaymentLog) {
            throw new Exception('Repayment log not found');
        }
        // Update the repayment log
        $repaymentLog->update($data);
        return $repaymentLog;
    }

    public function findByReference(string $reference): RepaymentLog
    {
        return RepaymentLog::whereReference($reference)->first();
    }

}
