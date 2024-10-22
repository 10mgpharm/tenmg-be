<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        // check if user is authenticated.
        if (! $user) {
            return false;
        }

        // check if user is an admin.
        return $user->hasRole('admin');
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_admin' => $this->input('isAdmin') ?: false,
            'is_supplier' => $this->input('isSupplier') ?: false,
            'is_pharmacy' => $this->input('isPharmacy') ?: false,
            'is_vendor' => $this->input('isVendor') ?: false,
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
            'name' => ['required', 'string', 'max:255', 'unique:notifications,name'],
            'description' => ['string', 'nullable', 'sometimes'],
            'is_admin' => ['required_if_declined:is_pharmacy,is_vendor,is_supplier'],
            'is_supplier' => ['required_if_declined:is_admin,is_pharmacy,is_vendor'],
            'is_pharmacy' => ['required_if_declined:is_admin,is_supplier,is_vendor'],
            'is_vendor' => ['required_if_declined:is_admin,is_supplier,is_pharmacy'],
            'active' => ['required', 'boolean'],
        ];
    }
}
