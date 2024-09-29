<?php

namespace App\Http\Controllers\API\Account;

use App\Http\Controllers\Controller;
use App\Enums\OtpType;
use App\Http\Resources\UserResource;
use App\Http\Requests\ResetTwoFactorRequest;
use App\Http\Requests\SetupTwoFactorRequest;
use App\Http\Requests\ToggleTwoFactorRequest;
use App\Http\Requests\VerifyTwoFactorRequest;
use App\Services\Interfaces\IAuthService;
use App\Services\OtpService;
use Illuminate\Support\Facades\DB;

class TwoFactorAuthenticationController extends Controller
{
    private $google2fa;

    public function __construct(private IAuthService $authService)
    {
        $this->authService = $authService;
        $this->google2fa = app('pragmarx.google2fa');
    }

    /**
     * Setup multi-factor authentication (MFA) for the user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setup(SetupTwoFactorRequest $request)
    {
        $user = $request->user();
        $two_factor_secret = $this->google2fa->generateSecretKey();

        DB::transaction(
            fn() =>
            $user->forceFill([
                'two_factor_secret' => encrypt($two_factor_secret),
                'use_two_factor' => true,
            ])->save()
        );

        $qrcode_xml = $this->google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $two_factor_secret
        );

        $data = [
            'qrcode_svg' => $qrcode_xml,
            'two_factor_secret' => $two_factor_secret,
        ];

        if (config('app.env') == 'local') {
            return response($qrcode_xml, 200)
                ->header('Content-Type', 'image/svg+xml');
        }

        return $this->returnJsonResponse(
            message: "Two-factor authentication setup completed.",
            data: $data
        );
    }

    /**
     * Verify the provided multi-factor authentication token.
     *
     * @param  \App\Http\Requests\VerifyTwoFactorRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(VerifyTwoFactorRequest $request)
    {
        try {
            $user = $request->user();

            DB::transaction(function () use ($user) {
                $user->forceFill(['use_two_factor' => true])->save();
            });

            $tokenResult = $user->createToken('Full Access Token', ['full']);

            return $this->authService->returnAuthResponse(
                user: $user,
                tokenResult: $tokenResult,
                message: 'Two-factor authentication verified successfully.'

            );
        } catch (\Throwable $th) {
            return $this->handleErrorResponse($th);
        }
    }

    /**
     * Reset multi-factor authentication for the user.
     *
     * @param  \App\Http\Requests\ResetTwoFactorRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(ResetTwoFactorRequest $request)
    {
        try {
            $user = $request->user();
            $otp = (new OtpService)->validate(OtpType::RESET_MULTI_FACTOR_AUTHENTICATION, $request->input('otp'));

            DB::transaction(function () use ($user, $otp) {
                $user->forceFill([
                    'two_factor_secret' => null,
                    'use_two_factor' => false,
                ])->save();

                $otp->delete();
            });

            return $this->returnJsonResponse(
                message: 'Multi-factor authentication has been reset. You will need to set it up again.',
                data: new UserResource($user->refresh())
            );
        } catch (\Throwable $th) {
            return $this->handleErrorResponse($th);
        }
    }

    /**
     * Toggle Two-Factor Authentication (2FA) on or off for the authenticated user.
     *
     * @param App\Http\Requests\ToggleTwoFactorRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle(ToggleTwoFactorRequest $request)
    {
        $user = $request->user();

        // Update the user's 2FA status
        $user->update([
            'use_two_factor' => boolval($request->input('use_two_factor')),
        ]);

        // Determine the status of 2FA
        $status = $request->input('use_two_factor') ? 'enabled' : 'disabled';

        // Return response with the updated user resource
        return $this->returnJsonResponse(
            message: '2FA ' . $status . ' successfully.',
            data: new UserResource($user->refresh())
        );
    }
}
