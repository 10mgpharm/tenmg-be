<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255|unique:credit_customers,email,'.$this->id.',id,business_id,'.$this->vendorId,
            'phone' => 'nullable|string|max:15',
            'active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'The customer name must be a string.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already associated with another customer in the same business.',
            'active.boolean' => 'The active status must be a boolean value.',
        ];
    }

    public function prepareForValidation()
    {
        if ($this->isMethod('patch')) {
            $this->merge([
                'active' => $this->input('active', null),
            ]);
        }
    }
}
