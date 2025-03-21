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
        $user = $request->user();

        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;


        $wallets = EcommerceTransaction::where('supplier_id', $business_id)
            ->paginate($request->input('perPage', 20))
            ->withQueryString()
            ->through(fn(EcommerceTransaction $item) => EcommerceTransactionResource::make($item));

        return $this->returnJsonResponse(
            message: 'Transactions fetched successfully.',
            data: $wallets,
        );
    }
}
