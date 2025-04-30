<?php

namespace App\Http\Controllers\API\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Resources\EcommerceOrderDetailResource;
use App\Http\Resources\Supplier\EcommerceTransactionResource;
use App\Models\EcommerceOrderDetail;
use App\Models\EcommerceTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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


        $wallets = EcommerceOrderDetail::where('supplier_id', $business_id)
        ->whereHas('order', fn($query) => $query->where('status', 'PROCESSING'))
            ->paginate($request->input('perPage', 20))
            ->withQueryString()
            ->through(fn(EcommerceOrderDetail $item) => EcommerceOrderDetailResource::make($item));

        return $this->returnJsonResponse(
            message: 'Transactions fetched successfully.',
            data: $wallets,
        );
    }
}
