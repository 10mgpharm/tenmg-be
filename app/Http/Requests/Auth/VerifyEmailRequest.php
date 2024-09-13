<?php

namespace App\Http\Requests\Auth;

use App\Enums\OtpType;
use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'otp' => [
                'required',
                'string',
                'size:6',
                'exists:otps,code,user_id,'.$this->user()->id.',type,'.OtpType::SIGNUP_EMAIL_VERIFICATION->value,
            ],

        ];
    }
}
