<?php

namespace App\Repositories;

use App\Models\Loan;
use App\Models\RepaymentSchedule;
use Illuminate\Database\Eloquent\Collection;

class LoanRepository
{
    public function store(array $data)
    {
        return Loan::create($data);
    }

    public function updateOrCreate(array $where, array $data)
    {
        return Loan::updateOrCreate($where, $data);
    }

    public function fetchLoansByCustomerId(int $customerId): Collection
    {
        return Loan::where('customer_id', $customerId)->get();
    }

    public function findById(int $id): ?Loan
    {
        return Loan::whereId($id)->with('repaymentSchedule')->first();
    }

    public function update(int $id, array $data): bool
    {
        $loan = Loan::findOrFail($id);
        $loan->update($data);
        return true;
    }

    public function fetchAllLoans(): Collection
    {
        return Loan::all();
    }

    public function getLoanList(array $criteria, $perPage = 15)
    {
        //get the business type
        $user = request()->user();
        $business = $user->ownerBusinessType
            ?: $user->businesses()->firstWhere('user_id', $user->id);

        $query = Loan::query();

        if (isset($criteria['search'])) {
            $query->whereHas('customer', function ($q) use ($criteria) {
                $q->where('name', 'like', '%'.$criteria['search'].'%');
            });
        }

        if (isset($criteria['dateFrom']) && isset($criteria['dateTo'])) {
            $query->whereBetween('created_at', [$criteria['dateFrom'], $criteria['dateTo']]);
        }

        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if($business->type != "ADMIN" && $business->type != "LENDER"){
            $query->where('customer_id', $user->id);
        }elseif($business->type == "LENDER"){
            $query->whereHas('offer', function ($q) use ($business) {
                $q->where('lender_id', $business->id);
            });
        }

        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    public function getLoanWithRepaymentSchedules(int $loanId): Loan
    {
        return Loan::with('repaymentSchedule')->findOrFail($loanId);
    }

    public function markLoanAsPaid(Loan $loan): bool
    {
        return $loan->update([
            'status' => 'PAID',
            'repaymemt_end_date' => now(),
        ]);
    }

    public function markLoanAsOngoingRepayment(Loan $loan): bool
    {
        return $loan->update([
            'status' => 'ONGOING_REPAYMENT'
        ]);
    }

    public function getLoanStats()
    {

        $totalCapital = Loan::sum('capital_amount');
        $activeLoan = Loan::where('status', 'DISBURSED')->sum('capital_amount');
        $totalInterest  = Loan::sum('interest_amount');


        return [
            "totalLoans" => $totalCapital,
            "totalAmountDisbursed" => $activeLoan,
            "totalInterest" => $totalInterest,
            "totalProduct" => 0

        ];

    }
}
