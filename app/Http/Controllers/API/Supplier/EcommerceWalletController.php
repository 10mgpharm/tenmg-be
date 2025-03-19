<?php

namespace App\Http\Controllers\API\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Resources\Supplier\EcommerceWalletResource;
use App\Models\EcommerceWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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


        $wallet = EcommerceWallet::where('business_id', $business_id)->first();

        return $this->returnJsonResponse(
            message: 'Wallet fetched successfully.',
            data: new EcommerceWalletResource($wallet),
        );
    }
}
