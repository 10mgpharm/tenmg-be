<?php

namespace App\Http\Requests\Wallet;

use App\Enums\WalletType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateWalletRequest extends FormRequest
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

        // Only vendors, lenders, and admin can create wallets
        return $user->hasRole('vendor') || $user->hasRole('lender') || $user->hasRole('admin');
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'wallet_type' => $this->input('walletType'),
            'currency_id' => $this->input('currencyId'),
            'wallet_name' => $this->input('walletName'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = $this->user();
        $business = $user->ownerBusinessType
            ?: $user->businesses()->firstWhere('user_id', $user->id);

        return [
            'wallet_type' => ['required', new Enum(WalletType::class)],
            'currency_id' => ['required', 'uuid', 'exists:currencies,id'],
            'wallet_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
