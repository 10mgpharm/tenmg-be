<?php

namespace App\Services;

use App\Models\Loan;
use App\Repositories\FincraMandateRepository;
use App\Repositories\RepaymentScheduleRepository;
use App\Services\PaystackService;
use Carbon\Carbon;

class RepaymentProcessingService
{
    public function __construct(private RepaymentScheduleRepository $repaymentScheduleRepository, private PaystackService $paystackService, private FincraMandateRepository $fincraMandateRepository) {}

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

    public function verifyRepaymentLink($reference)
    {
        //get loan by reference
        $loan = $this->repaymentScheduleRepository->getLoanByReference($reference);
        return $loan;

    }

    public function makeRepayment($request)
    {
        $reference = $request->input('reference');
        $loan = Loan::where('identifier', $reference)->first();

        $payment = $this->fincraMandateRepository->debitCustomerMandate($loan->application_id);

        return $payment;


    }
}
