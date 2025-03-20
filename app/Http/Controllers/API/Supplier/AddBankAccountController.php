<?php

namespace App\Http\Controllers\API\Supplier;

use App\Enums\OtpType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\AddBankAccountRequest;
use App\Http\Resources\Supplier\EcommerceBankAccountResource;
use App\Models\EcommerceBankAccount;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AddBankAccountController extends Controller
{
    /**
     * Handle the request to add a new bank account.
     * 
     * This method validates the OTP, associates the bank account with the user's business,
     * and saves the bank account details in the database within a transaction.
     */
    public function __invoke(AddBankAccountRequest $request): JsonResponse
    {
        
        $user = $request->user();

        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $validated = $request->validated();
        $validated['supplier_id'] = $business_id;

        
        return DB::transaction(function () use ($validated) {
            $otp = (new OtpService)->validate(OtpType::SUPPLIER_ADD_BANK_ACCOUNT, $validated['otp']);

            $bank_account = EcommerceBankAccount::create($validated);
            
            $otp->delete();

            return $this->returnJsonResponse(
                message: 'Bank account added successfully.',
                data: new EcommerceBankAccountResource($bank_account),
            );
        });


    }
}
