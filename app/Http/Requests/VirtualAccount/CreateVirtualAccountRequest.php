<?php

namespace App\Http\Requests\VirtualAccount;

use App\Enums\VirtualAccountType;
use App\Models\Wallet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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

        return $user->hasRole('vendor') || $user->hasRole('lender') || $user->hasRole('admin');
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'wallet_id' => $this->input('walletId'),
            'type' => $this->input('type'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'uuid', 'exists:wallets,id'],
            'type' => ['required', new Enum(VirtualAccountType::class)],
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
