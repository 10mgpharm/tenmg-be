<?php

namespace App\Http\Resources\Lender;

use App\Http\Resources\LoadApplicationResource;
use App\Models\CreditLenderPreference;
use App\Models\CreditOffer;
use App\Models\CreditTransactionHistory;
use App\Models\LoanApplication;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LenderDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $user = request()->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $ignoredIds = CreditLenderPreference::where('lender_id', $business_id)->first()->ignored_applications_id;

        // Get loan applications with status 'pending'
        $loanRequests = LoanApplication::query();

        $loanRequests->when(!empty($ignoredIds ?? []), function ($querySub) use ($ignoredIds) {
            $querySub->whereNotIn('id', $ignoredIds);
        });

        $loansApp = $loanRequests->where('status', 'INITIATED')->orderBy("created_at", 'DESC')->take(5)->get();

        // Get the total count of all pending loan requests
        $totalCount = LoanApplication::where('status', 'INITIATED')->whereNotIn('id', $ignoredIds ?? [])->count();

        $records = CreditOffer::whereMonth('created_at', $currentMonth)
                         ->whereYear('created_at', $currentYear)->where('lender_id', $business_id)
                         ->get();

        $interestEarned = CreditTransactionHistory::where('business_id', $business_id)->where('transaction_group', 'repayment_interest')->sum('amount');


        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'wallets' => $this->allLendersWallet,
            'loanRequest' => LoadApplicationResource::collection($loansApp),
            'loanApprovalThisMonth' => $records->count(),
            'interestEarned' => $interestEarned,
            'pendingRequests' => $totalCount

        ];
    }
}
