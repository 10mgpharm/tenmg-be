<?php

namespace App\Http\Controllers\API\Lender;

use App\Http\Controllers\Controller;
use App\Services\Lender\LoanPreferenceService;
use Illuminate\Http\Request;

class LoanPreferenceController extends Controller
{

    private $loanPreferenceService;

    function __construct(LoanPreferenceService $loanPreferenceService)
    {
        $this->loanPreferenceService = $loanPreferenceService;
    }

    public function createUpdateLoanPreference(Request $request)
    {
        $request->validate([
            'loanTenure' => 'required|in:3,6,9,12',
            'creditScoreCategory' => 'required|in:A,B,C,D',
        ]);

        $user = $request->user();
            $business_id = $user->ownerBusinessType?->id
                ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $request->merge(['business_id' => $business_id]);

        $loanPreference = $this->loanPreferenceService->createUpdateLoanPreference($request);

        return $this->returnJsonResponse(
            data: $loanPreference,
            message: 'Loan Preference Updated Successfully',
            status: 200
        );

    }

}
