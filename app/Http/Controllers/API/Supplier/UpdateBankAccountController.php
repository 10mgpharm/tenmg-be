<?php

namespace App\Http\Controllers\API\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\UpdateBankAccountRequest;
use App\Http\Resources\Supplier\EcommerceBankAccountResource;
use App\Models\EcommerceBankAccount;
use App\Models\EcommerceWallet;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class UpdateBankAccountController extends Controller
{
    /**
     * Handle the request to edit a bank account.
     * 
     * This method validates the OTP, associates the bank account with the user's business,
     * and saves the bank account details in the database within a transaction.
     */
    public function __invoke(UpdateBankAccountRequest $request, EcommerceBankAccount $bank_account): JsonResponse
    {
        
        $user = $request->user();

        $business = $user->ownerBusinessType
            ?: $user->businesses()->firstWhere('user_id', $user->id);

        $validated = $request->validated();
        $validated['supplier_id'] = $business?->id;

        
        return DB::transaction(function () use ($validated, $bank_account, $business) {
            $updated = $bank_account->update(array_filter($validated, fn($value) => !is_null($value)));


        if (! $updated) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t update bank account at the moment. Please try again later.'
            );
        }

        AuditLogService::log(
            target: $bank_account, // The user is the target (it is being updated)
            event: 'bank.account.updated',
            action: "Bank account updated",
            description: "{$business?->name} bank account was updated.",
            crud_type: 'UPDATE', // Use 'UPDATE' for updating actions
        );
        
            return $this->returnJsonResponse(
                message: 'Bank account updated successfully.',
                data: new EcommerceBankAccountResource($bank_account->refresh()),
            );
        });


    }
}
