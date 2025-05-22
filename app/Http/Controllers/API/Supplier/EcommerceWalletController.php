<?php

namespace App\Http\Controllers\API\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Resources\Supplier\EcommerceWalletResource;
use App\Models\EcommerceOrderDetail;
use App\Models\EcommerceWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EcommerceWalletController extends Controller
{
    /**
     * Handle the request to fetch the e-commerce wallet for the supplier's business.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;


        $wallet = EcommerceWallet::with('bankAccount')->where('business_id', $business_id)->first();

        // Calculate the total pending supplier payout (Credit - Debit)
        $total_pending_payouts = EcommerceOrderDetail::query()
        ->where('supplier_id', $business_id)
        ->whereHas('order', fn ($query) => $query->where('status', 'PROCESSING'))
        ->sum(DB::raw('(COALESCE(discount_price, actual_price))'));

        return $this->returnJsonResponse(
            message: 'Wallet fetched successfully.',
            data: [
                'wallet' => new EcommerceWalletResource($wallet),
                'pendingBalance' => $total_pending_payouts,
            ],
        );
    }
}
