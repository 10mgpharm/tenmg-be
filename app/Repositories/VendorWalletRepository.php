<?php

namespace App\Repositories;

use App\Models\CreditTransactionHistory;
use App\Models\CreditVendorWallets;
use App\Models\Loan;

class VendorWalletRepository
{

    public function getWalletStats($businessId = null)
    {
        $business_id = null;
        if($businessId) {
            $business_id = $businessId;
        }else{
            $user = request()->user();
            $business_id = $user->ownerBusinessType?->id
                ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;
        }

            $financials = $this->getBusinessFinancials($business_id);
            $onGoingLoans = $financials['onGoingLoans'];
            $voucherBalance = $financials['voucherBalance'];
            $payoutBalance = $financials['payoutBalance'];

            return $record = [
                'onGoingLoans' => $onGoingLoans,
                'voucherBalance' => $voucherBalance->current_balance ?? 0,
                'payoutBalance' => $payoutBalance->current_balance ?? 0
            ];

    }

    public function getBusinessFinancials($business_id)
    {
        return [
            'onGoingLoans' => Loan::where('business_id', $business_id)
                ->where('status', 'Ongoing')
                ->sum('capital_amount'),

            'voucherBalance' => CreditVendorWallets::where('vendor_id', $business_id)
                ->where('type', 'credit_voucher')
                ->first('current_balance'),

            'payoutBalance' => CreditVendorWallets::where('vendor_id', $business_id)
                ->where('type', 'payout')
                ->first('current_balance')
        ];
    }

    public function getTransactions():\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {



        $perPage = request()->query('perPage') ?? 15;
        $business_id = request()->query('businessId');
        $dateFrom = request()->query('dateFrom');
        $dateTo = request()->query('dateTo');
        $status = request()->query('status');

        if($business_id == null) {
            $user = request()->user();
            $business_id = $user->ownerBusinessType?->id
                ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;
        }

        $query = CreditTransactionHistory::query();

        $query->when(
            isset($dateFrom) && isset($dateTo),
            function ($query) use ($dateFrom, $dateTo) {
                // Parse dates with Carbon to ensure proper format
                $dateFrom = \Carbon\Carbon::parse($dateFrom)->startOfDay();
                $dateTo = \Carbon\Carbon::parse($dateTo)->endOfDay();

                return $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            }
        );

        $query->when(isset($status), function ($query) use ($status) {
            return $query->where('status', $status['status']);
        });

        $query->where('business_id', $business_id)->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }
}
