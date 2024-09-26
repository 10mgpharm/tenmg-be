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
            'provider' => ['required', 'string', 'in:google'],
            'name' => [
                'required', 
                'string', 
                'max:255',
            ],
            'email' => [
                'required', 
                'string', 
                'lowercase', 
                'email',
                'max:255',
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
