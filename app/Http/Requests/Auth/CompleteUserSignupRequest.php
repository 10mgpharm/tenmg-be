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
            'business_type' => strtoupper($this->input('businessType', ''))
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
            'business_type' => [
                'required_if:provider,google',
                'string',
                Rule::exists('businesses', 'type')
                ->where('owner_id', $this->user()->id),
            ],
        ];
    }
}
