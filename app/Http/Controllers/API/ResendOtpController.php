<?php

namespace App\Http\Controllers\API;

use App\Enums\OtpType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResendOtpRequest;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use App\Models\User;

class ResendOtpController extends Controller
{
    /**
     * Regenerate OTP and send it to the user's email.
     */
    public function __invoke(ResendOtpRequest $request): JsonResponse
    {
        try {
            $user = $request->user() ?: User::firstWhere('email', $request->input('email'));

            if ($user) {
                $otpType = OtpType::from($request->input('type'));
                (new OtpService)->forUser($user)->regenerate($otpType)->sendMail($otpType);
            }

            return $this->returnJsonResponse(
                message: 'A one-time password has been resent to your registered email.',
            );
        } catch (\Throwable $th) {
            return $this->handleErrorResponse($th);
        }

        return $this->returnJsonResponse(
            message: 'A one-time password has been resent to your registered email.',
        );
    }
}
