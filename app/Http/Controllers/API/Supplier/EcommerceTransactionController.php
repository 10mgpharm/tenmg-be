<?php

namespace App\Http\Controllers\API\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Resources\Supplier\EcommerceTransactionResource;
use App\Models\EcommerceTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcommerceTransactionController extends Controller
{
    /**
     * Handle the request to fetch e-commerce transactions for the supplier's business.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $business_id = $request->input('businessId');

        if($request->input('businessId') == null) {
            $user = $request->user();
            $business_id = $user->ownerBusinessType?->id
                ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        }


        $wallets = EcommerceTransaction::where('supplier_id', $business_id)
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
            ->when($request->input('fromDate'), fn($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($request->input('toDate'), fn($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latest('id')
            ->paginate($request->input('perPage', 20))
            ->withQueryString()
            ->through(fn(EcommerceTransaction $item) => EcommerceTransactionResource::make($item));

        return $this->returnJsonResponse(
            message: 'Transactions fetched successfully.',
            data: $wallets,
        );
    }
}
