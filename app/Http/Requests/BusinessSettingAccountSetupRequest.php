<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BusinessSettingAccountSetupRequest extends FormRequest
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
            'license_number' => $this->input('licenseNumber'),
            'expiry_date' => $this->input('expiryDate'),
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
            'license_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('businesses', 'license_number')->ignore($this->user()->id, 'owner_id'),
            ],
            'expiry_date' => ['required', 'date'],
            'cacDocument' => [
                'sometimes',
                'nullable',
                'mimes:pdf,doc,docx',
                'extensions:pdf,doc,docx',
                'max:10240',
            ],

        ];
    }
}
