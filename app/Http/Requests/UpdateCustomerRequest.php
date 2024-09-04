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
            'business_id' => 'required|exists:businesses,id',
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255|unique:credit_customers,email,'.$this->customer->id.',id,business_id,'.$this->business_id,
            'phone' => 'nullable|string|max:15',
            'avatar' => 'nullable|image|mimes:png,jpg|max:2048', // Assuming file upload is an image
            'active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'business_id.required' => 'The business ID is required.',
            'business_id.exists' => 'The selected business does not exist.',
            'name.string' => 'The customer name must be a string.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already associated with another customer in the same business.',
            'avatar.image' => 'The avatar must be an image file.',
            'avatar.max' => 'The avatar size should not exceed 2MB.',
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
