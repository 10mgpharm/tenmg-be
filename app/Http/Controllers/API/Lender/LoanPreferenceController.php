<?php

namespace App\Http\Controllers\API\Lender;

use App\Http\Controllers\Controller;
use App\Services\Lender\LoanPreferenceService;
use Illuminate\Http\Request;

class LoanPreferenceController extends Controller
{
    private $loanPreferenceService;

    public function __construct(LoanPreferenceService $loanPreferenceService)
    {
        $this->loanPreferenceService = $loanPreferenceService;
    }

    public function createUpdateLoanPreference(Request $request)
    {
        $request->validate([
            'loanTenure' => 'required|array',
            'loanTenure.*' => 'in:1,2,3,4',
            'creditScoreCategory' => 'required|array',
            'creditScoreCategory.*' => 'in:A,B,C,D',
        ]);

        $user = $request->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $request->merge(['business_id' => $business_id]);

        $loanPreference = $this->loanPreferenceService->createUpdateLoanPreference($request);

        return $this->returnJsonResponse(
            data: $loanPreference,
            message: 'Loan Preference Updated Successfully'
        );

    }

    public function getLoanPreference()
    {

        $loanPreference = $this->loanPreferenceService->getLoanPreference();

        return $this->returnJsonResponse(
            data: $loanPreference,
            message: 'Loan Preference Successfully fetched'
        );
    }

    public function getLoanPreferencePrefill()
    {
        $prefill = $this->loanPreferenceService->getLoanPreferencePrefill();

        return $this->returnJsonResponse(
            data: $prefill,
            message: 'Loan Preference prefill'
        );
    }

    public function updateAutoAcceptStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $loanPreference = $this->loanPreferenceService->updateAutoAcceptStatus($request);

        return $this->returnJsonResponse(
            data: $loanPreference,
            message: 'Loan Preference auto accept status updated'
        );

    }
}
