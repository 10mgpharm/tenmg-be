<?php

namespace App\Http\Requests\Storefront;

use App\Enums\StatusEnum;
use App\Models\ShippingAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateShippingAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $shipping_address = $this->route('shipping_address');

        if ($user->hasRole('customer') && $shipping_address->business_id === ($user->ownerBusinessType->id ?? $user->businesses()->first()?->id)) {
            return true;
        }

        return false;
    }

    
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {

        $this->merge([
            'name' => $this->input('name'),
            'address' => $this->input('address'),
            'phone_number' => $this->input('phoneNumber'),
            'country' => $this->input('country'),
            'state' => $this->input('state'),
            'city' => $this->input('city'),
            'zip_code' => $this->input('zipCode'),
            'is_default' => $this->input('isDefault'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Retrieve the current medication type from the route
        $shipping_address = $this->route('shipping_address');

        return [

            // Product Basic
            'name' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique(ShippingAddress::class, 'name')->ignore($shipping_address->id)],
            'address' => ['sometimes', 'nullable', 'string', 'min:3'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'country' => ['sometimes', 'nullable', 'string', 'max:255'],
            'state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'zip_code' => ['sometimes', 'nullable', 'string', 'max:255', 'min:3'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Custom response for failed authorization.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function failedAuthorization()
    {
        abort(response()->json([
            'message' => 'You are not authorized to update this resource.',
        ], 403));
    }
}
