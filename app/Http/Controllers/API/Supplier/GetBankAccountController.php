<?php

namespace App\Http\Controllers\API\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\GetBankAccountRequest;
use App\Http\Resources\Supplier\EcommerceBankAccountResource;
use App\Models\EcommerceBankAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class GetBankAccountController extends Controller
{
    /**
     * Handle the request to get a bank account.
     * 
     * @param GetBankAccountRequest $request
     */
    public function __invoke(GetBankAccountRequest $request): JsonResponse
    {
        
        $user = $request->user();

        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $bank_account = EcommerceBankAccount::query()
            ->where('supplier_id', $business_id)
            ->first();
        
        return $this->returnJsonResponse(
            message: 'Bank account fetched successfully.',
            data: $bank_account ? new EcommerceBankAccountResource($bank_account) : null,
        );

    }
}
