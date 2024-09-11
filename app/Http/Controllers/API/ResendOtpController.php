<?php

namespace App\Http\Controllers\API;

use App\Enums\OtpType;
use App\Services\OtpService;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResendOtpRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ResendOtpController extends Controller
{

    /**
     * Regenerate OTP and send it to the user's email.
     */
    public function __invoke(ResendOtpRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Unauthenticated.');
            }

            $otpType = OtpType::from($request->input('type'));
            $otp = (new OtpService)->regenerate($otpType);

            $user->sendEmailVerification($otp->code);
            return response()->json(['message' => 'A one-time password has been resent to your registered email.'], Response::HTTP_OK);

        } catch (\Throwable $th) {
            return $this->handleErrorResponse($th);
        }
    }
}
