<?php

namespace App\Http\Requests\VirtualAccount;

use App\Models\Wallet;
use Illuminate\Foundation\Http\FormRequest;

class CreateVirtualAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $wallet = Wallet::find($this->input('walletId'));

        if (! $wallet) {
            return false;
        }

        // Check if user owns the business that owns the wallet
        $business = $user->ownerBusinessType
            ?: $user->businesses()->firstWhere('user_id', $user->id);

        if ($wallet->business_id !== $business?->id) {
            return false;
        }

        // Check if virtual account already exists for this wallet
        if ($wallet->virtualAccount()->exists()) {
            return false;
        }

        // Only lenders and admins can create virtual accounts
        return $user->hasRole('lender') || $user->hasRole('admin');
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'wallet_id' => $this->input('walletId'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     * Note: Only lenders can create virtual accounts.
     * Account type is automatically determined by lender_type:
     * - Individual lenders → INDIVIDUAL
     * - Business lenders → CORPORATE
     */
    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'uuid', 'exists:wallets,id'],
        ];
    }

    /**
     * Custom response for failed authorization.
     */
    public function failedAuthorization()
    {
        $wallet = Wallet::find($this->input('walletId'));

        if ($wallet && $wallet->virtualAccount()->exists()) {
            abort(response()->json([
                'message' => 'Virtual account already exists for this wallet.',
            ], 400));
        }

        abort(response()->json([
            'message' => 'Unauthorized to create virtual account.',
        ], 403));
    }
}
