<?php

namespace App\Http\Controllers\API\Supplier;

use App\Constants\EcommerceWalletConstants;
use App\Http\Controllers\Controller;
use App\Http\Resources\EcommerceOrderDetailResource;
use App\Http\Resources\Supplier\EcommerceTransactionResource;
use App\Models\EcommerceOrderDetail;
use App\Models\EcommerceTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EcommercePendingPayoutController extends Controller
{
    /**
     * Handle the request to fetch e-commerce transactions for the supplier's business.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;


        // Calculate the total pending supplier payout (Credit - Debit)
        // $total_pending_payouts = EcommerceOrderDetail::query()
        // ->where('supplier_id', $business_id)
        // ->whereHas('order', fn ($query) => $query->where('status', 'PROCESSING'))
        // ->sum(DB::raw('(COALESCE(discount_price, actual_price))'));

        $payouts = EcommerceTransaction::query()
        ->where('supplier_id', $business_id)
        ->whereIn('txn_group', [EcommerceWalletConstants::SUPPLIER_TXN_GROUP_ORDER_PAYMENT])
        ->whereIn('txn_type', [EcommerceWalletConstants::TXN_TYPE_CREDIT])
        ->when(
            $request->input('search'),
            fn($query, $search) => $query->where(
                fn($query) => $query->where('txn_type', 'like', "%{$search}%")
                    ->orWhere('txn_group', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhere('balance_before', 'like', "%{$search}%")
                    ->orWhere('balance_after', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn($query) => $query->where('name', 'like', "%{$search}%")->orWhere('short_name', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn($query) => $query->where('identifier', 'like', "%{$search}%"))

            )
        )
        ->latest('id')
        ->paginate($request->get('perPage', 30))
        ->withQueryString()
        ->through(fn(EcommerceTransaction $message) => new EcommerceTransactionResource($message));

        // Fetch transactions with pagination
        $transactions = EcommerceTransaction::query()
        ->when(
            $request->input('search'),
            fn($query, $search) => $query->where(
                fn($query) => $query->where('txn_type', 'like', "%{$search}%")
                    ->orWhere('txn_group', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhere('balance_before', 'like', "%{$search}%")
                    ->orWhere('balance_after', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn($query) => $query->where('name', 'like', "%{$search}%")->orWhere('short_name', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn($query) => $query->where('identifier', 'like', "%{$search}%"))

            )
        )
        ->latest('id')
        ->paginate($request->get('perPage', 30))
        ->withQueryString()
        ->through(fn(EcommerceTransaction $message) => new EcommerceTransactionResource($message));

        $pending_payouts = EcommerceOrderDetail::where('supplier_id', $business_id)
            ->whereHas('order', fn($query) => $query->where('status', 'PROCESSING'))
            ->select('ecommerce_order_id', 'actual_price', 'discount_price', 'tenmg_commission', 'created_at', 'supplier_id', 'ecommerce_product_id',)
            ->when(
                $request->input('search'),
                fn($query, $search) => $query->where(
                    fn($query) => $query->where('actual_price', 'like', "%{$search}%")
                        ->orWhere('discount_price', 'like', "%{$search}%")
                        ->orWhereHas('product', fn($query) => $query->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"))
                )
                // ->orWhereHas('order', fn($query) => $query->where('order_number', 'like', "%{$search}%"))
            )
            ->latest('id')
            ->paginate($request->input('perPage', 20))
            ->withQueryString()
            ->through(fn(EcommerceOrderDetail $item) => EcommerceOrderDetailResource::make($item));

        return $this->returnJsonResponse(
            message: 'Transactions fetched successfully.',
            data: [
                'pendingPayouts' => $pending_payouts,
                'payouts' => $payouts,
                'transactions' => $transactions,
                // 'totalPendingPayouts' => $total_pending_payouts,
            ],
        );
    }
}
