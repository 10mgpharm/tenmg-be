<?php

namespace App\Http\Requests\Auth;

use App\Enums\BusinessType;
use App\Rules\BusinessEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class SignupUserRequest extends FormRequest
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
     * This will remap `passwordConfirmation` to `password_confirmation` for Laravel's default validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'password_confirmation' => $this->input('passwordConfirmation'),
            'lender_type' => $this->input('lenderType'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Fetch all business types allowed for registration and convert them to lowercase
        $allowedBusinessTypes = array_map(fn ($type) => $type->toLowerCase(), BusinessType::allowedForRegistration());

        return [
            'businessType' => [
                'required',
                'string',
                'in:'.implode(',', $allowedBusinessTypes),
            ],
            'lenderType' => [
                'required_if:businessType,lender',
                'string',
                'in:individual,business',
            ],
            'fullname' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255', 'unique:businesses,name'],
            'email' => [
                'required', 'string', 'lowercase', 'email',
                'max:255', 'unique:users,email',
                new BusinessEmail,
            ],
            'password' => ['required', Rules\Password::default()],
            'passwordConfirmation' => ['required', 'same:password'],
            'termsAndConditions' => 'required|accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'termsAndConditions.required' => 'You must agree to the terms and conditions.',
            'businessType.in' => 'The business type must be either supplier, pharmacy, vendor or lender',
            'lenderType.required_if' => 'The lender type is required when registering as a lender.',
            'lenderType.in' => 'The lender type must be either individual or business.',
        ];
    }
}
