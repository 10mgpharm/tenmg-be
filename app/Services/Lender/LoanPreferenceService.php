<?php

namespace App\Services\Lender;

use App\Models\Affordability;
use App\Models\CreditLenderPreference;
use App\Settings\CreditSettings;
use Illuminate\Http\Request;

class LoanPreferenceService
{
    public function createUpdateLoanPreference(Request $request)
    {

        $creditSettings = new CreditSettings;
        $loanInterest = $creditSettings->interest_config;

        $createUpdatePrefs = CreditLenderPreference::UpdateOrCreate(
            [
                'lender_id' => $request->business_id,
            ],
            [
                'lender_id' => $request->business_id,
                'loan_tenure' => $request->loanTenure ?? [3],
                'loan_interest' => $loanInterest,
                'credit_score_category' => $request->creditScoreCategory ?? ['A'],
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

        $creditSettings = new CreditSettings;
        $loanInterest = $creditSettings->interest_config;

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
