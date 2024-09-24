<?php

namespace App\Http\Requests;

use App\Helpers\UtilityHelper;
use App\Rules\BusinessEmail;
use Illuminate\Foundation\Http\FormRequest;

class AuthProviderRequest extends FormRequest
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
            'provider' => ['required', 'string', 'in:google,credentials'],
            'businessType' => [
                'string',
                'in:' . implode(',', UtilityHelper::getAllowedBusinessTypes()),
                // Require businessType only if user is not authenticated (sign up)
                $this->user() ? 'nullable' : 'required',
            ],
            'name' => [
                'required', 
                'string', 
                'max:255',
                // Unique name for businesses, considering owner_id for existing users
                'unique:businesses,name,' . $this->user()?->id . ',owner_id'
            ],
            'email' => [
                'required', 
                'string', 
                'lowercase', 
                'email',
                'max:255',
                // Unique email for users, considering the user ID for updates
                'unique:users,email,' . $this->user()?->id . ',id',
                new BusinessEmail,
            ],
            // 'email_verified' => ['required', 'boolean'],
            'picture' => ['sometimes', 'string'],
        ];
    }

    /**
     * Custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }
}
