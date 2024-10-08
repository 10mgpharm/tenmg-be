<?php

namespace App\Http\Requests;

use App\Rules\BusinessEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BusinessSettingPersonalInformationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()->ownerBusinessType;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->input('businessName'),
            'contact_person' => $this->input('contactPerson'),
            'contact_phone' => $this->input('contactPhone'),
            'contact_email' => $this->input('contactEmail'),
            'contact_person_position' => $this->input('contactPersonPosition'),
            'address' => $this->input('businessAddress'),
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
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('businesses', 'name')->ignore($this->user()->id, 'owner_id'),
            ],
            'contact_person' => ['sometimes', 'nullable', 'string', 'min:3', 'max:255'],
            'contact_phone' => [
                'sometimes',
                'nullable',
                'string',
                'min:3',
                'max:255',
            ],
            'contact_email' => [
                'sometimes',
                'nullable',
                'string',
                'min:3',
                'max:255',
                'email',
                new BusinessEmail,
            ],
            // 'contact_person_position' => ['sometimes', 'nullable', 'string', 'min:3', 'max:255',],
            'address' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
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
            'name.string' => 'The business name field must be a string.',
            'name.max' => 'The business name field must not be greater than 255 characters.',
            'name.unique' => 'The business name has already been taken.',

            'address.string' => 'The business address field must be a string.',
            'address.max' => 'The business address field must not be greater than 255 characters.',
        ];
    }
}
