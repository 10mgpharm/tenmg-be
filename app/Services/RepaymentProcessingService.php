<?php

namespace App\Services;

use App\Repositories\RepaymentScheduleRepository;
use App\Services\PaystackService;
use Carbon\Carbon;

class RepaymentProcessingService
{
    public function __construct(private RepaymentScheduleRepository $repaymentScheduleRepository, private PaystackService $paystackService) {}

    /**
     * Process repayments due today.
     */
    public function processRepayments()
    {
        $today = Carbon::now()->startOfDay();
        $repayments = $this->repaymentScheduleRepository->getRepaymentsDueOnDate($today);

        foreach ($repayments as $repayment) {
            $this->paystackService->debitCustomer($repayment);
        }
    }
}
