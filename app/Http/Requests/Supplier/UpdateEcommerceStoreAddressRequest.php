<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEcommerceStoreAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        $store_address = $this->route('store_address');

        $business_id = $user->ownerBusinessType?->id
        ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        // Suppliers can only update store_addresses created by their business
        if ($user->hasRole('supplier') && $store_address->business_id === $business_id) {
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
            'closest_landmark' => $this->input('closestLandmark'),
            'street_address' => $this->input('streetAddress'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'country' => ['required', 'string', 'max:255', 'min:3'],
            'state' => ['required', 'string', 'max:255', 'min:3'],
            'city' => ['required', 'string', 'max:255', 'min:3'],
            'closest_landmark' => ['sometimes', 'nullable', 'string', 'max:255', 'min:3'],
            'street_address' => ['required', 'string', 'max:255', 'min:3'],
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
            'message' => 'You are not authorized to create this resource.',
        ], 403));
    }
}
