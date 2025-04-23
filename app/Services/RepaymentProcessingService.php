<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\RepaymentSchedule;
use App\Repositories\FincraMandateRepository;
use App\Repositories\RepaymentScheduleRepository;
use App\Services\PaystackService;
use Carbon\Carbon;

class RepaymentProcessingService
{
    public function __construct(private RepaymentScheduleRepository $repaymentScheduleRepository, private PaystackService $paystackService, private FincraMandateRepository $fincraMandateRepository, private NotificationService $notificationService) {}

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

    public function initiateRepayment($request)
    {
        $reference = $request->input('reference');
        $loan = Loan::where('identifier', $reference)->first();

        // $payment = $this->fincraMandateRepository->debitCustomerMandate($loan->application_id);

        $initPaymentData = $this->repaymentScheduleRepository->initiateRepayment($request);

        return $initPaymentData;


    }

    /**
     * Test the sendReminders method.
     *
     * @return void
     */
    public function sendRemindersTest($loanRef)
    {
        $loan = Loan::where('identifier', $loanRef)->first();
        if (!$loan) {
            throw new \Exception('Loan not found');
        }

        $repayments = RepaymentSchedule::where('loan_id', $loan->id)
            ->where('payment_status', 'PENDING')
            ->with(['loan.customer']) // eager load related customer and loan info
            ->get();

        if (count($repayments) < 1) {
            throw new \Exception('No pending loan repayment');
        }

        // foreach ($repayments as $repayment) {
            $this->notificationService->sendRepaymentReminder($repayments[0]);


        return $loan;

    }

    public function cancelPayment($paymentRef)
    {
        return $this->repaymentScheduleRepository->cancelPayment($paymentRef);

    }
}
