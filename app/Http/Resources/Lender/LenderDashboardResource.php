<?php

namespace App\Http\Resources\Lender;

use App\Http\Resources\LoadApplicationResource;
use App\Models\CreditOffer;
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

        // Get loan applications with status 'pending'
        $loanRequests = LoanApplication::where('status', 'pending')->orderBy("created_at", 'DESC')->take(5)->get();

        // Get the total count of all pending loan requests
        $totalCount = LoanApplication::where('status', 'pending')->count();

        $records = CreditOffer::whereMonth('created_at', $currentMonth)
                         ->whereYear('created_at', $currentYear)->where('lender_id', $business_id)
                         ->get();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'wallets' => $this->allLendersWallet,
            'loanRequest' => LoadApplicationResource::collection($loanRequests),
            'loanApprovalThisMonth' => $records->count(),
            'interestEarned' => 0,
            'pendingRequests' => $totalCount

        ];
    }
}
