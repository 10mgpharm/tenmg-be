<?php

namespace App\Http\Requests\Auth;

use App\Constants\PublicDomainConstants;
use App\Enums\BusinessType;
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
            'fullname' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255', 'unique:businesses,name'],
            'email' => [
                'required', 'string', 'lowercase', 'email',
                'max:255', 'unique:users,email',
                function ($attribute, $value, $fail) {
                    $domain = substr(strrchr($value, '@'), 1);

                    if (
                        in_array($domain, PublicDomainConstants::PUBLIC_DOMAINS) &&
                        request()->input('businessType') == BusinessType::VENDOR->toLowercase()
                    ) {
                        $fail('Public email providers are not allowed. Please use a business email.');
                    }
                },
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
            'businessType.in' => 'The business type must be either supplier, pharmacy or vendor',
        ];
    }
}
