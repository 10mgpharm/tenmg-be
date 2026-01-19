<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceProviderRequest extends FormRequest
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

        // Only admin can update service providers
        return $user->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'config' => ['sometimes', 'nullable', 'array'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'is_bvn_verification_provider' => ['sometimes', 'boolean'],
            'is_virtual_account_provider' => ['sometimes', 'boolean'],
            'is_virtual_card_provider' => ['sometimes', 'boolean'],
            'is_physical_card_provider' => ['sometimes', 'boolean'],
            'is_checkout_provider' => ['sometimes', 'boolean'],
            'is_bank_payout_provider' => ['sometimes', 'boolean'],
            'is_mobile_money_payout_provider' => ['sometimes', 'boolean'],
            'is_identity_verification_provider' => ['sometimes', 'boolean'],
            'currencies_supported' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ];
    }
}
