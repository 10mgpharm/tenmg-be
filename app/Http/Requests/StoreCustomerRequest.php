<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $vendorBusiness = auth()->user()?->businesses()
            ->where('type', 'VENDOR')
            ->first();

        $this->merge([
            'vendorId' => $vendorBusiness?->id,
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:credit_customers,email,NULL,id,business_id,'.$this->vendorId,
            'phone' => 'nullable|string|max:15',
            'reference' => 'nullable',
            'file' => 'nullable|mimes:csv,xlsx,json|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The customer name is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already associated with a customer in the same business.',
            'email.required' => 'The customer email is required.',
            'phone.string' => 'The phone number must be a string.',
            'phone.max' => 'The phone number should not exceed 15 characters.',
            'file.mimes' => 'The file must be a file of type: csv, xlsx, json.',
            'file.max' => 'The file size should not exceed 2048 kilobytes.',
        ];
    }
}
