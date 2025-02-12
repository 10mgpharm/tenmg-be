<?php

namespace App\Services\Lender;

use App\Models\CreditLenderBankAccounts;
use Illuminate\Http\Request;

class BankAccountService
{

    public function addUpdateBankAccount(Request $request)
    {
        try {
            $user = $request->user();
            $business_id = $user->ownerBusinessType?->id
                ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

            $creditLenderBank = CreditLenderBankAccounts::UpdateOrCreate(
                [
                    'lender_id' => $business_id
                ],
                [
                    'bank_name' => $request->bankName,
                    'bank_code' => $request->bankCode,
                    'account_name' => $request->accountName,
                    'account_number' => $request->accountNumber,
                    'bvn' => $request->bvn
                ]);

            return $creditLenderBank;


        } catch (\Throwable $th) {
            throw $th;
        }
    }

}
