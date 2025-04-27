<?php

namespace App\Http\Controllers\API\Admin;

use App\Constants\EcommerceWalletConstants;
use App\Http\Controllers\Controller;
use App\Http\Resources\Supplier\EcommerceTransactionResource;
use App\Models\EcommerceTransaction;
use App\Models\TenMgWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EcommerceWalletController extends Controller
{
    /**
     * Handle the request to fetch the temg wallet.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $filter = $request->input('date_filter', 'ONE_YEAR');

        // Get the current timestamp
        $now = now();

        // Determine the date range based on the filter value
        $date_range = match (strtoupper($filter)) {
            'ONE_WEEK' => [$now->copy()->subWeek(), $now],
            'TWO_WEEKS' => [$now->copy()->subWeeks(2), $now],
            'ONE_MONTH' => [$now->copy()->subMonth(), $now],
            'THREE_MONTHS' => [$now->copy()->subMonths(3), $now],
            'SIX_MONTHS' => [$now->copy()->subMonths(6), $now],
            'ONE_YEAR' => [$now->copy()->subYear(), $now],
            default => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
        };

        // Get tenmg wallet
        $wallet = TenMgWallet::query()
            ->select('id', 'previous_balance', 'current_balance')
            ->latest()
            ->first();

        // Calculate the total commissions earned (Credit - Debit)
        $total_commissions_earned = EcommerceTransaction::query()
            ->whereIn('txn_group', [
                EcommerceWalletConstants::TENMG_TXN_GROUP_ORDER_PAYMENT,
                EcommerceWalletConstants::TENMG_TXN_GROUP_ORDER_CANCELLATION
            ])
            ->whereBetween('created_at', $date_range) 
            ->selectRaw(
                'SUM(CASE WHEN txn_type = ? THEN amount ELSE 0 END) - SUM(CASE WHEN txn_type = ? THEN amount ELSE 0 END) as net_commission',
                [
                    EcommerceWalletConstants::TXN_TYPE_CREDIT,
                    EcommerceWalletConstants::TXN_TYPE_DEBIT
                ]
            )
            ->value('net_commission');

        // Calculate the total supplier payout (Credit - Debit)
        $total_supplier_payout = EcommerceTransaction::query()
            ->whereIn('txn_group', [
                EcommerceWalletConstants::SUPPLIER_TXN_GROUP_ORDER_PAYMENT,
                EcommerceWalletConstants::SUPPLIER_TXN_GROUP_ORDER_CANCELLATION
            ])
            ->whereBetween('created_at', $date_range)
            ->selectRaw(
                'SUM(CASE WHEN txn_type = ? THEN amount ELSE 0 END) - SUM(CASE WHEN txn_type = ? THEN amount ELSE 0 END) as net_commission',
                [
                    EcommerceWalletConstants::TXN_TYPE_CREDIT,
                    EcommerceWalletConstants::TXN_TYPE_DEBIT
                ]
            )
            ->value('net_commission');

        // Fetch transactions with pagination
        $payouts = EcommerceTransaction::query()
            ->where('txn_group', EcommerceWalletConstants::SUPPLIER_TXN_GROUP_ORDER_PAYMENT)
            ->where('txn_type', EcommerceWalletConstants::TXN_TYPE_CREDIT)
            ->whereBetween('created_at', $date_range)
            ->latest()
            ->paginate($request->get('perPage', 30))
            ->withQueryString()
            ->through(fn(EcommerceTransaction $message) => new EcommerceTransactionResource($message));


        $transactions = EcommerceTransaction::query()
            ->whereBetween('created_at', $date_range)
            ->latest()
            ->paginate($request->get('perPage', 30))
            ->withQueryString()
            ->through(fn(EcommerceTransaction $message) => new EcommerceTransactionResource($message));

        // Return the response
        return $this->returnJsonResponse(
            message: 'Wallet and transaction data fetched successfully.',
            data: [
                'wallet' => $wallet,
                'total_commissions_earned' => $total_commissions_earned,
                'total_supplier_payout' => $total_supplier_payout,
                'transactions' => $transactions,
                'payouts' => $payouts,
            ]
        );
    }
}
