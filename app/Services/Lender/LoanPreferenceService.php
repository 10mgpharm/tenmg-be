<?php

namespace App\Services\Lender;

use App\Models\creditLendersPreference;
use App\Settings\CreditSettings;
use Illuminate\Http\Request;

class LoanPreferenceService
{

    public function createUpdateLoanPreference(Request $request)
    {

        $creditSettings = new CreditSettings();
        $loanInterest = $creditSettings->interest_config;

        $createUpdatePrefs = creditLendersPreference::UpdateOrCreate(
            [
                'lender_id' => $request->business_id
            ],
            [
                'lender_id' => $request->business_id,
                'loan_tenure' => $request->loanTenure ?? [3],
                'loan_interest' => $loanInterest,
                'credit_score_category' => $request->creditScoreCategory ?? ["A"]
            ]
        );

        return $createUpdatePrefs;
    }

}
