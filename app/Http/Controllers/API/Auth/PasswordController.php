<?php

namespace App\Http\Controllers\API\Auth;

use App\Enums\OtpType;
use App\Helpers\UtilityHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    /**
     * Send a password reset link.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::firstWhere(['email' => $request->input('email')]);

        if ($user) {
            $otp = Otp::create([
                'code' => UtilityHelper::generateOtp(),
                'type' => OtpType::RESET_PASSWORD_VERIFICATION,
                'user_id' => $user->id,
            ]);
            $user->sendPasswordResetNotification($otp->code);
        }

        return $this->returnJsonResponse(
            message: 'A one-time password has been sent to your registered email',
            statusCode: Response::HTTP_OK
        );
    }

    /**
     * Handle an incoming new password request.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $user = User::firstWhere('email', $request->input('email'));

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            'remember_token' => Str::random(60),
        ])->save();

        $user->otps()->firstWhere([
            'code' => $request->input('otp'),
            'type' => OtpType::RESET_PASSWORD_VERIFICATION,
        ])->delete();

        return $this->returnJsonResponse(
            message: __('passwords.reset'),
            statusCode: Response::HTTP_OK
        );
    }
}
