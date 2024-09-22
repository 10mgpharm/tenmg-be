<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Services\Interfaces\IAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerifyEmailController extends Controller
{
    /**
     * verify user constructor
     */
    public function __construct(private IAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(VerifyEmailRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Unauthenticated.');
            }

            $verifiedUser = $this->authService->verifyUserEmail($user, $request->input('otp'));

            return $this->returnJsonResponse(
                message: 'User verified',
                data: [
                    'emailVerifiedAt' => $verifiedUser?->email_verified_at,
                ],
                status: Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            return $this->handleErrorResponse($th);
        }
    }
}
