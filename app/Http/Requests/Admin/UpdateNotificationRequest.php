<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        // Check if user is authenticated and is an admin
        return $user && $user->hasRole('admin');
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
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:notifications,name,'.$this->notification->id,
            ],
            'description' => ['string', 'nullable', 'sometimes'],
            'is_admin' => ['required_if:is_supplier,true', 'required_if:is_pharmacy,true', 'required_if:is_vendor,true'],
            'is_supplier' => ['required_if:is_admin,true', 'required_if:is_pharmacy,true', 'required_if:is_vendor,true'],
            'is_pharmacy' => ['required_if:is_admin,true', 'required_if:is_supplier,true', 'required_if:is_vendor,true'],
            'is_vendor' => ['required_if:is_admin,true', 'required_if:is_supplier,true', 'required_if:is_pharmacy,true'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
