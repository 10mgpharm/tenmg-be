<?php

namespace App\Services\Lender;

use App\Models\Affordability;
use App\Models\CreditLenderPreference;
use App\Settings\CreditSettings;
use App\Settings\LoanSettings;
use Illuminate\Http\Request;

class LoanPreferenceService
{
    public function createUpdateLoanPreference(Request $request)
    {

        $loanSettings = new LoanSettings;
        $loanInterest = $loanSettings->lenders_interest;

        $tenure = $request->loanTenure ?? [3];
        $credit_score = $request->creditScoreCategory ?? ['A'];

        $createUpdatePrefs = CreditLenderPreference::UpdateOrCreate(
            [
                'lender_id' => $request->business_id,
            ],
            [
                'lender_id' => $request->business_id,
                'loan_tenure' => $tenure,
                'loan_interest' => $loanInterest,
                'credit_score_category' => $credit_score,
            ]
        );

        return $createUpdatePrefs;
    }

    public function getLoanPreference()
    {
        $user = request()->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $loanPreference = CreditLenderPreference::where('lender_id', $business_id)->first();

        return $loanPreference;
    }

    public function getLoanPreferencePrefill()
    {

        $categories = Affordability::select('category as value', 'lower_bound as loanAbove')
            ->get()
            ->toArray();
        $loanTenure = ['3', '6', '9', '12'];

        $loanTenureResult = array_map(function ($item) {
            return [
                'value' => (int) $item,
                'label' => "{$item} months",
            ];
        }, $loanTenure);

        $loanSettings = new LoanSettings();
        $loanInterest = $loanSettings->lenders_interest;

        $data = [
            'categories' => $categories,
            'loanTenure' => $loanTenureResult,
            'interestRate' => 'Your interest '.$loanInterest.'%, Processing fee (0%)',
        ];

        return $data;

    }

    public function updateAutoAcceptStatus(Request $request)
    {
        $user = request()->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $loanPreference = CreditLenderPreference::where('lender_id', $business_id)->first();
        $loanPreference->auto_accept = $request->status;
        $loanPreference->save();

        return $loanPreference;
    }
}
