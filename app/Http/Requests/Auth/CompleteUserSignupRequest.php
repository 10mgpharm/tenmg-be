<?php

namespace App\Http\Requests\Auth;

use App\Enums\BusinessStatus;
use App\Helpers\UtilityHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteUserSignupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return !!$this->user();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'contact_phone' => $this->input('contactPhone'),
            'contact_person' => $this->input('contactPerson'),
            'contact_person_position' => $this->input('contactPersonPosition'),
            'type' => strtoupper($this->input('businessType', ''))
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
            'contact_phone' => ['required', 'string', 'min:3', 'max:255', Rule::unique('users')->ignore($this->user()->id),],
            'contact_person' => ['required', 'string', 'min:3', 'max:255'],
            'contactPersonPosition' => ['required', 'string', 'min:3', 'max:255', ],
            'name' => ['required', 'string', 'max:255', 'exists:businesses,name,status,' . BusinessStatus::PENDING_VERIFICATION->value . ',owner_id,' . $this->user()->id], 
            'termsAndConditions' => ['required', 'accepted'],
            'provider' => ['required', 'in:google,credentials'],
            'type' => [
                'required_if:provider,google',
                'string',
                Rule::exists('businesses', 'type')
                ->where('owner_id', $this->user()->id),
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
        ];
    }
}
