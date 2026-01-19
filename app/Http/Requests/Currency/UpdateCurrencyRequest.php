<?php

namespace App\Http\Requests\Currency;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCurrencyRequest extends FormRequest
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

        // Only admin can update currencies
        return $user->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'classification' => ['sometimes', 'string', 'in:fiat,crypto'],
            'name' => ['sometimes', 'string', 'max:125'],
            'code' => ['sometimes', 'string', 'max:10'],
            'symbol' => ['sometimes', 'nullable', 'string', 'max:10'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:10'],
            'decimal_places' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:10'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'tier_1_limits' => ['sometimes', 'nullable', 'array'],
            'tier_2_limits' => ['sometimes', 'nullable', 'array'],
            'tier_3_limits' => ['sometimes', 'nullable', 'array'],
            'country_code' => ['sometimes', 'nullable', 'string', 'max:3'],
            'virtual_account_provider' => ['sometimes', 'nullable', 'uuid', 'exists:service_providers,id'],
            'temp_virtual_account_provider' => ['sometimes', 'nullable', 'uuid', 'exists:service_providers,id'],
            'virtual_card_provider' => ['sometimes', 'nullable', 'uuid', 'exists:service_providers,id'],
            'bank_transfer_collection_provider' => ['sometimes', 'nullable', 'uuid', 'exists:service_providers,id'],
            'mobile_money_collection_provider' => ['sometimes', 'nullable', 'uuid', 'exists:service_providers,id'],
            'bank_transfer_payout_provider' => ['sometimes', 'nullable', 'uuid', 'exists:service_providers,id'],
            'mobile_money_payout_provider' => ['sometimes', 'nullable', 'uuid', 'exists:service_providers,id'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
