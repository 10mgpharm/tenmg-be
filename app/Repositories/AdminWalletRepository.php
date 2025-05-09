<?php

namespace App\Repositories;

use App\Http\Resources\Lender\LenderDashboardResource;
use App\Models\Business;
use App\Models\CreditLendersWallet;
use App\Models\CreditTransactionHistory;
use App\Models\CreditVendorWallets;
use App\Models\EcommerceOrderDetail;
use App\Models\EcommerceWallet;
use App\Models\Loan;
use App\Models\TenmgTransactionHistory;
use App\Models\TenMgWallet;
use Illuminate\Support\Facades\DB;

class AdminWalletRepository
{

    public function __construct(private VendorWalletRepository $vendorWalletRepository, private LenderDashboardRepository $lenderDashboardRepository)
    {
        //
    }

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

    public function getAdminTransactions():\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = request()->query('perPage') ?? 15;
        $query = TenmgTransactionHistory::orderBy('created_at', 'desc')->paginate($perPage);

        return $query;
    }

    public function getPayOutTransactions():\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = request()->query('perPage') ?? 15;
        $query = CreditTransactionHistory::where('transaction_group', 'payout')
            ->orderBy('created_at', 'desc')->paginate($perPage);

        return $query;
    }

    public function getWalletUserStats($businessId)
    {

        $business = Business::find($businessId);
        if (!$business) {
            throw new \Exception('Business not found');
        }

        $response = null;

        switch ($business->type) {
            case 'SUPPLIER':
                $response = $this->getSupplierWalletStats($businessId);
                break;
            case 'VENDOR':
                $response = $this->vendorWalletRepository->getWalletStats($businessId);
                break;
            case 'LENDER':
                $lender = $this->lenderDashboardRepository->getDashboardStats($businessId)->allLendersWallet;
                $response =  [
                    'deposit' => collect($lender)->firstWhere('type', 'deposit')->current_balance,
                    'investment' => collect($lender)->firstWhere('type', 'investment')->current_balance,
                    'ledger' => collect($lender)->firstWhere('type', 'ledger')->current_balance,
                ];
                break;

            default:
                # code...
                break;
        }

        return $response;

    }

    public function getSupplierWalletStats($business_id)
    {

        $total_pending_payouts = EcommerceOrderDetail::query()->where('supplier_id', $business_id)->whereHas('order', fn ($query) => $query->where('status', 'PROCESSING'))->sum(DB::raw('(COALESCE(discount_price, actual_price))'));

        $wallet = EcommerceWallet::where('business_id', $business_id)->first();

        return [
            'totalPendingOrder' => $total_pending_payouts,
            'walletBalance' => $wallet->current_balance
        ];

    }

    public function getUserTransactionHistory($businessId):\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $business = Business::find($businessId);
        if (!$business) {
            throw new \Exception('Business not found');
        }

        $response = null;

        switch ($business->type) {
            case 'SUPPLIER':
                $response = $this->getSupplierWalletStats($businessId);
                break;
            case 'VENDOR':
                $response = $this->vendorWalletRepository->getTransactions($businessId);
                break;
            case 'LENDER':
                $lender = $this->lenderDashboardRepository->getDashboardStats($businessId)->allLendersWallet;
                $response =  [
                    'deposit' => collect($lender)->firstWhere('type', 'deposit')->current_balance,
                    'investment' => collect($lender)->firstWhere('type', 'investment')->current_balance,
                    'ledger' => collect($lender)->firstWhere('type', 'ledger')->current_balance,
                ];
                break;

            default:
                # code...
                break;
        }

        return $response;

    }
}
