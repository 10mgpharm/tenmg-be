<?php

namespace App\Http\Controllers\API\Auth;

use App\Enums\OtpType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\Interfaces\IAuthService;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    /**
     * signup user contructor
     */
    public function __construct(private IAuthService $authService)
    {
        $this->authService = $authService;
    }

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
            (new OtpService)->forUser($user)
                ->generate(OtpType::RESET_PASSWORD_VERIFICATION)
                ->sendMail(OtpType::RESET_PASSWORD_VERIFICATION);
        }

        $tokenResult = $user->createToken('Full Access Token', ['full']);

        return $this->authService->returnAuthResponse(
            message: 'A one-time password has been sent to your registered email',
            user: $user,
            tokenResult: $tokenResult,
            statusCode: Response::HTTP_OK
        );
    }

    /**
     * Handle an incoming new password request.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            'remember_token' => Str::random(60),
        ])->save();

        $user->token()->revoke();

        return $this->returnJsonResponse(
            message: __('passwords.reset'),
            statusCode: Response::HTTP_OK
        );
    }
}
