<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class PasswordUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return !! $this->user();
    }


    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'current_password' => $this->input('currentPassword'),
            'new_password' => $this->input('newPassword'),
            'new_password_confirmation' => $this->input('newPasswordConfirmation'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
                'max:255',
                'current_password:api'
            ],
            'new_password' => [
                'sometimes',
                'nullable',
                'required_with:new_password_confirmation',
                'string',
                Rules\Password::default(),
                'confirmed',
                'different:current_password'
            ],
        ];
    }
}
