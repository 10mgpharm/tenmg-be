<?php

namespace App\Services;

use App\Models\Loan;
use App\Repositories\LoanRepository;
use App\Repositories\RepaymentScheduleRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LoanService
{
    public function __construct(
        private LoanRepository $loanRepository,
        private RepaymentScheduleRepository $repaymentScheduleRepository
    ) {}

    /**
     * Create a loan and generate repayment schedules.
     *
     * @return CreditLoan
     */
    public function createLoan(array $loanData, array $repaymentBreakdown): Loan
    {
        try {
            // Create the loan record
            $loan = $this->loanRepository->updateOrCreate([
                'business_id' => $loanData['business_id'],
                'customer_id' => $loanData['customer_id'],
                'application_id' => $loanData['application_id'],
            ], $loanData);

            // Generate repayment schedules
            foreach ($repaymentBreakdown as $schedule) {
                $this->repaymentScheduleRepository->store([
                    'loan_id' => $loan->id,
                    'amount' => $schedule['totalPayment'],
                    'due_date' => Carbon::parse($schedule['month'])->endOfMonth(),
                    'payment_status' => 'PENDING',
                ]);
            }

            // Return the created loan
            return $loan;
        } catch (\Exception $e) {
            Log::error('Failed to create loan', ['error' => $e->getMessage()]);
            throw new \Exception('Failed to create loan');
        }
    }

    /**
     * Mark a loan as disbursed and update its status.
     */
    public function markAsDisbursed(int $loanId): Loan
    {

        $loan = $this->loanRepository->findById($loanId);

        if (!$loan) {
            throw new \Exception('Loan not found', 404);
        }

        if ($loan->status === 'DISBURSED') {
            throw new \Exception('Loan has already been disbursed', 400);
        }

        // Update the loan status
        $this->loanRepository->update($loan->id, [
            'status' => 'DISBURSED',
            'repaymemt_start_date' => Carbon::now(),
            'repaymemt_end_date' => Carbon::now()->addMonths((int)$loan?->application?->duration_in_months)
        ]);

        return $this->loanRepository->findById($loanId);
    }

    /**
     * Fetch the repayment schedule for a loan.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRepaymentSchedules(int $loanId)
    {
        return $this->repaymentScheduleRepository->fetchRepaymentScheduleByLoanId($loanId);
    }

    public function getLoanById(int $loanId): Loan
    {
        return $this->loanRepository->findById($loanId);
    }

    public function getAllLoans(): \Illuminate\Support\Collection
    {
        return $this->loanRepository->fetchAllLoans();
    }
}
