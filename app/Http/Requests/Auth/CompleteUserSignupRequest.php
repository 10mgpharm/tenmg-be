<?php

namespace App\Http\Requests\Auth;

use App\Enums\BusinessType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteUserSignupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->input('businessName'),
            'type' => $this->input('businessType', null),
            'contact_email' => $this->input('businessEmail'),
            'contact_phone' => $this->input('contactPhone'),
            'contact_person' => $this->input('contactPersonName'),
            'contact_person_position' => $this->input('contactPersonPosition'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $allowedBusinessTypes = array_map(fn ($type) => $type->toLowerCase(), BusinessType::allowedForRegistration());

        return [
            'contact_email' => ['required', 'string', 'email', 'max:255'],
            'contact_phone' => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],
            'contact_person' => ['required', 'string', 'min:3', 'max:255'],
            'contact_person_position' => ['required', 'string', 'min:3', 'max:255'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('businesses', 'name')
                    ->where(function ($query) {
                        return $query->where('owner_id', '!=', $this->user()->id);
                    }),
            ],
            'termsAndConditions' => ['required', 'accepted'],
            'provider' => ['required', 'in:google,credentials'],
            'type' => [
                'nullable',
                'required_if:provider,google',
                'string',
                'in:'.implode(',', $allowedBusinessTypes),
            ],
        ];
    }

    /**
     * Custom error messages for validation failures.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'type.exists' => 'The selected business type does not match your account.',
            'type.required_if' => 'The business type is required when provider is google.',
            'type.string' => 'The business type must be a string.',
            'type.in' => 'The business type must be either supplier or customer_pharmacy',

            'contact_email.required' => 'The business email field is required.',
            'contact_email.string' => 'The business email field must be a string.',
            'contact_email.email' => 'The business email field must be a valid email address.',
            'contact_email.max' => 'The business email field must not be greater than :max characters.',

            'contact_person.required' => 'The contact person name field is required.',
            'contact_person.string' => 'The contact person name field must be a string.',
            'contact_person.max' => 'The contact person name field must not be greater than :max characters.',
            'contact_person.min' => 'The contact person name field must not be lesser than :min characters.',

            'name.required' => 'The business name field is required.',
            'name.string' => 'The business name field must be a string.',
            'name.max' => 'The business name field must not be greater than :max characters.',
        ];
    }
}
