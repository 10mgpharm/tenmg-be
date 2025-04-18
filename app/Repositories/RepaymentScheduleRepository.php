<?php

namespace App\Repositories;

use App\Models\Loan;
use App\Models\RepaymentSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class RepaymentScheduleRepository
{
    public function store(array $data)
    {
        return RepaymentSchedule::create($data);
    }

    // fetch repayment schedule by loan id
    public function fetchRepaymentScheduleByLoanId(int $loanId): Collection
    {
        return RepaymentSchedule::where('loan_id', $loanId)->get();
    }

    // fetch repayment schedule by cusstomer id
    public function fetchRepaymentScheduleByCustomerId(int $customerId): Collection
    {
        //credit_loans
        return RepaymentSchedule::whereHas('loan', function ($query) use ($customerId) {
            $query->where('customer_id', $customerId);
        })->get();
    }

    // fetch repayment schedule by id
    public function fetchRepaymentScheduleById($id): RepaymentSchedule
    {
        return RepaymentSchedule::find($id);
    }

    // update repayment schedule by id
    public function updateRepaymentScheduleById($id, array $data): bool
    {
        return RepaymentSchedule::where('id', $id)->update($data);
    }

    // delete repayment schedule by id
    public function deleteRepaymentScheduleById($id)
    {
        return RepaymentSchedule::where('id', $id)->delete();
    }

    // delete repayment schedule by loan id
    public function deleteRepaymentScheduleByLoanId($loanId)
    {
        return RepaymentSchedule::where('loan_id', $loanId)->delete();
    }

    /**
     * Get repayment schedules due on a specific date.
     */
    public function getRepaymentsDueOnDate(Carbon $dueDate)
    {
        return RepaymentSchedule::whereDate('due_date', $dueDate)
            ->where('payment_status', 'PENDING')
            ->with(['loan.customer']) // eager load related customer and loan info
            ->get();
    }

    public function findProcessingRepayments(): Collection
    {
        return RepaymentSchedule::where('payment_status', 'PROCESSING')->get();
    }

    public function markRepaymentsAsCancelled(int $loanId): bool
    {
        return RepaymentSchedule::where('loan_id', $loanId)
            ->where('payment_status', 'PENDING')
            ->orWhere('payment_status', 'PROCESSING')
            ->update(['payment_status' => 'CANCELLED']);
    }

    public function processRepaymentForLoan(int $loanId)
    {
        $latestRepayment = RepaymentSchedule::where('loan_id', $loanId)
            ->where('payment_status', 'PENDING')
            ->orderBy('id', 'asc')
            ->first();

        //check if we are on production or test
        if (config('app.env') != 'production') {

            $latestRepayment->payment_status = 'PAID';
            $latestRepayment->save();



        }
    }

    public function getLoanByReference($reference)
    {
        $loan = Loan::where('identifier', $reference)->first();
        if (!$loan) {
            throw new \Exception('Loan not found');
        }

        // Check if the loan is already paid
        if ($loan->status === 'Completed') {
            throw new \Exception('Loan is already paid');
        }

        return $loan;
    }


}
