<?php

namespace App\Http\Requests;

use App\Enums\OtpType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResetTwoFactorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'otp' => [
                'required',
                'string',
                Rule::exists('otps', 'code')
                    ->where('type', OtpType::RESET_MULTI_FACTOR_AUTHENTICATION->value)
                    ->where('user_id', $this->user()->id),
            ],
            'password' => ['required', 'max:255', 'current_password:api'],
        ];
    }
}
