<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SignupUserRequest;
use App\Http\Resources\UserResource;
use App\Services\Interfaces\IAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SignupUserController extends Controller
{
    /**
     * signup user controller
     */
    public function __construct(private IAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming signup request.
     *
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(SignupUserRequest $request): JsonResponse
    {
        try {
            $request->validated();
            $user = $this->authService->signUp($request);
            $tokenResult = $user->createToken('Temporary Access Token', ['temp']);

            return (new UserResource($user))
                ->additional([
                    'accessToken' => [
                        'token' => $tokenResult->accessToken,
                        'tokenType' => 'bearer',
                        'expiresAt' => $tokenResult->token->expires_at,
                    ],
                    'message' => 'Sign up successful. Please verify your email using the OTP sent.',
                    'status' => 'success',
                ])
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);

        } catch (\Throwable $th) {
            return $this->handleErrorResponse($th);
        }
    }
}
