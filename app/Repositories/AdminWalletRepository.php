<?php

namespace App\Repositories;

use App\Models\CreditLendersWallet;
use App\Models\CreditTransactionHistory;
use App\Models\CreditVendorWallets;
use App\Models\Loan;
use App\Models\TenMgWallet;

class AdminWalletRepository
{

    public function getWalletStats()
    {
        $user = request()->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

            $financials = $this->getBusinessFinancials($business_id);
            $totalLenders = $financials['totalLenders'];
            $vendorPayouts = $financials['vendorPayouts'];
            $walletBalance = $financials['walletBalance'];

            return $record = [
                'totalLenders' => $totalLenders,
                'vendorPayouts' => $vendorPayouts,
                'walletBalance' => $walletBalance->current_balance ?? 0
            ];

    }

    public function getBusinessFinancials($business_id)
    {
        return [
            'totalLenders' => CreditLendersWallet::where('type', 'deposit')
                ->sum('current_balance'),

            'vendorPayouts' => CreditVendorWallets::where('type', 'payout')
                ->sum('current_balance'),

            'walletBalance' => TenMgWallet::first('current_balance')
        ];
    }

    public function getTransactions():\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = request()->query('perPage') ?? 15;
        $query = CreditTransactionHistory::orderBy('created_at', 'desc')->paginate($perPage);

        return $query;
    }
}
