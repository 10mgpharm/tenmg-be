<?php

namespace App\Repositories;

use App\Models\CreditTransactionHistory;
use App\Models\Loan;
use App\Models\RepaymentSchedule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
        return Loan::whereId($id)->with('repaymentSchedule', 'application', 'customer')->first();
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
            $query->where('identifier', 'like', $criteria['search'])->orWhereHas('customer', function ($q) use ($criteria) {
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
            $query->where('business_id', $business->id);
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
        $user = request()->user();
        $business = $user->ownerBusinessType
            ?: $user->businesses()->firstWhere('user_id', $user->id);

        $totalCapitalQuery = Loan::query();
        $activeLoanQuery = Loan::query();
        $totalInterestQuery  = RepaymentSchedule::query();
        $pendingRepaymentQuery = RepaymentSchedule::query();

        if($business->type != "ADMIN" && $business->type != "LENDER"){
            $totalCapitalQuery->where('business_id', $business->id);
            $activeLoanQuery->where('business_id', $business->id);
            $totalInterestQuery->where('payment_status', 'PAID')->whereHas('loan', function($query) use ($business) {
                $query->where('business_id', $business->id);
            });
            $pendingRepaymentQuery->whereHas('loan', function($query) use ($business) {
                $query->where('business_id', $business->id);
            });

        }elseif($business->type == "LENDER"){
            $totalCapitalQuery->whereHas('offer', function ($q) use ($business) {
                $q->where('lender_id', $business->id);
            });
            $activeLoanQuery->whereHas('offer', function ($q) use ($business) {
                $q->where('lender_id', $business->id);
            });
            $pendingRepaymentQuery->whereHas('loan.offer', function ($q) use ($business) {
                $q->where('lender_id', $business->id);
            });
        }

        $totalCapital = $totalCapitalQuery->sum('capital_amount');
        $activeLoan = $activeLoanQuery->count();
        $totalInterest = $totalInterestQuery->sum('interest');
        $pendingRepayment = $pendingRepaymentQuery->where('payment_status', 'PENDING')->sum('total_amount');
        $completedRepayment = Loan::whereDoesntHave('repaymentSchedule', function($query) {
            $query->where('payment_status', '!=', 'PAID');
        })->count();

        if($business->type != "ADMIN"){
            return [
                "totalLoans" => $totalCapital,
                "activeLoan" => $activeLoan,
                "pendingRepayment" => $pendingRepayment,
                'completedRepayment' => $completedRepayment
            ];
        }

        return [
            "totalLoans" => $totalCapital,
            "totalInterest" => $totalInterest,
            "activeLoan" => $activeLoan,
            "pendingRepayment" => $pendingRepayment

        ];

    }

    public function getLoanStatusCount()
    {
        $user = request()->user();
        $business = $user->ownerBusinessType
            ?: $user->businesses()->firstWhere('user_id', $user->id);

        if($business->type != "ADMIN" && $business->type != "LENDER"){
            $allLoan = Loan::where('business_id', $business->id)->count();
            $ongoingLoan = Loan::where('status', 'Ongoing')->where('business_id', $business->id)->count();
            $completedLoan = Loan::where('status', 'Completed')->where('business_id', $business->id)->count();
            $lateRepayment = Loan::where('status', 'Late Repayment')->where('business_id', $business->id)->count();

            return [
                "All" => $allLoan,
                "Ongoing" => $ongoingLoan,
                "Completed" => $completedLoan,
                "LateRepayment" => $lateRepayment,
            ];

        }elseif($business->type == "LENDER"){
            $allLoan = Loan::whereHas('offer', function ($q) use ($business) {
                $q->where('lender_id', $business->id);
            })->count();
            $ongoingLoan = Loan::where('status', 'Ongoing')->whereHas('offer', function ($q) use ($business) {
                $q->where('lender_id', $business->id);
            })->count();
            $completedLoan = Loan::where('status', 'Completed')->whereHas('offer', function ($q) use ($business) {
                $q->where('lender_id', $business->id);
            })->count();
            $lateRepayment = Loan::where('status', 'Late Repayment')->whereHas('offer', function ($q) use ($business) {
                $q->where('lender_id', $business->id);
            })->count();

            return [
                "All" => $allLoan,
                "Ongoing" => $ongoingLoan,
                "Completed" => $completedLoan,
                "LateRepayment" => $lateRepayment,
            ];
        }

        $allLoan = Loan::all()->count();
        $ongoingLoan = Loan::where('status', 'Ongoing')->count();
        $completedLoan = Loan::where('status', 'Completed')->count();
        $lateRepayment = Loan::where('status', 'Late Repayment')->count();

        return [
            "All" => $allLoan,
            "Ongoing" => $ongoingLoan,
            "Completed" => $completedLoan,
            "LateRepayment" => $lateRepayment,
        ];
    }

    public function getEarnings()
    {

        $user = request()->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;


        $totalInterest = DB::table('credit_loans')
            ->join('credit_offers', 'credit_loans.offer_id', '=', 'credit_offers.id')
            ->join('credit_applications', 'credit_offers.application_id', '=', 'credit_applications.id')
            ->where('credit_offers.lender_id', $business_id)->where('credit_loans.status', '=', 'Ongoing')
            ->select('credit_applications.*')
            ->sum('actual_interest');

        $repaidInterest = CreditTransactionHistory::where('type', 'CREDIT')->where('transaction_group', 'repayment_interest')->where('business_id', $business_id)->sum('amount');

        $totalBalanceInterest = DB::table('credit_repayment_schedules')
            ->join('credit_loans', 'credit_repayment_schedules.loan_id', '=', 'credit_loans.id')
            ->join('credit_offers', 'credit_loans.offer_id', '=', 'credit_offers.id')
            ->join('credit_applications', 'credit_offers.application_id', '=', 'credit_applications.id')
            ->where('credit_offers.lender_id', $business_id)->where('credit_repayment_schedules.payment_status', 'PENDING')
            ->select('credit_repayment_schedules.*')
            ->sum('actual_interest');

        return [
                'totalProjectedInterest' => round($totalInterest, 0),
                'totalRepaidInterest' => round($repaidInterest, 0),
                'totalBalanceInterest' => round($totalBalanceInterest, 0)
            ];
    }

    public function getEarningHistory(array $filters, int $perPage = 15):\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {

        $user = request()->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $query = Loan::query();

        $query->whereHas('offer', function ($query) use ($business_id) {
            $query->where('lender_id', $business_id);
        })->get();

        $query->when(isset($filters['search']), function ($query) use ($filters) {
            $searchTerm = "%{$filters['search']}%";
            return $query->where(function ($query) use ($searchTerm) {
                $query->orWhereHas('customer', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', $searchTerm);
                    });
            });
        });

        return $query->paginate($perPage);

    }
}
