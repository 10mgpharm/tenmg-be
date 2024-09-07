<?php

namespace App\Http\Requests\Auth;

use App\Enums\OtpType;
use App\Models\User;
use App\Services\AuthService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class ResetPasswordRequest extends FormRequest
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
            'otp' => [
                'required',
                'string',
                'exists:otps,code,type,'.OtpType::RESET_PASSWORD_VERIFICATION,
            ],
            'email' => ['required', Rule::exists(User::class, 'email')],
            'password' => ['required', Rules\Password::default()],
            'passwordConfirmation' => ['required', 'same:password'],
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
        $code = $this->input('otp');

        $user = User::where('email', $email)->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'otp' => [__('Invalid email or otp is incorrect. Please try again.')],
            ]);
        }

        $otp = $user->otps()->firstWhere('code', $code);

        if (! $otp || Carbon::parse($otp->created_at)->diffInMinutes(now()) > AuthService::TOKEN_EXPIRED_AT) {
            throw ValidationException::withMessages([
                'otp' => [__('The OTP provided is incorrect or has expired. Please try again.')],
            ]);
        }
    }
}
