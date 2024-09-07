<?php

namespace App\Http\Requests\Auth;

use App\Enums\OtpType;
use Illuminate\Validation\Rules;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ResetPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
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
            'otp' => ['required', 'string',],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    /**
     * Validate the request data.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function prepareForValidation()
    {
        $this->validateOtp();
    }

    /**
     * Validate the OTP.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateOtp()
    {
        $email = $this->input('email');
        $otp = $this->input('otp');

        $user = User::where('email', $email)->first();

        // Ensure OTP is provided
        if (empty($otp)) {
            throw ValidationException::withMessages([
                'otp' => [__('The OTP is required.')],
            ]);
        }

        // Check if OTP is valid for the given user
        if (!$user || !$user->otps()->firstWhere([
            'code' => $otp,
            'user_id' => $user->id,
            'type' => OtpType::RESET_PASSWORD_VERIFICATION
        ])) {
            throw ValidationException::withMessages([
                'otp' => [__('The OTP provided is incorrect or has expired. Please try again.')],
            ]);
        }
    }
}
