<?php

namespace App\Http\Controllers\API;

use App\Enums\OtpType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResendOtpRequest;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ResendOtpController extends Controller
{
    /**
     * Regenerate OTP and send it to the user's email.
     */
    public function __invoke(ResendOtpRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Unauthenticated.');
        }

        $otpType = OtpType::from($request->input('type'));
        (new OtpService)->regenerate($otpType)->sendMail($otpType);

        return $this->returnJsonResponse(
            message: 'A one-time password has been resent to your registered email.',
        );
    }
}
