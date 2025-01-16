<?php

namespace App\Http\Controllers\API\Auth;

use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AuthenticatedRequest;
use App\Http\Requests\AuthProviderRequest;
use App\Services\Interfaces\IAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class AuthenticatedController extends Controller
{
    /**
     * signup user contructor
     */
    public function __construct(private IAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(AuthenticatedRequest $request): JsonResponse
    {
        try {
            if (! $request->authenticate()) {
                return response()->json([
                    'message' => 'Email or Password is invalid',
                    'status' => 'error',
                ], Response::HTTP_UNAUTHORIZED);
            }

            $user = $request->user();

            if ($user->getRawOriginal('status') === StatusEnum::INACTIVE->value) {

                return response()->json([
                    'message' => 'Your account is inactive. Please contact support.',
                    'status' => 'error',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($user->getRawOriginal('status') === StatusEnum::SUSPENDED->value) {

                return response()->json([
                    'message' => 'Your account is suspended. Please contact support.',
                    'status' => 'error',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($user->getRawOriginal('status') !== StatusEnum::ACTIVE->value) {
                return response()->json([
                    'message' => 'Your account is banned. Please contact support.',
                    'status' => 'error',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $tokenResult = $user->createToken('Full Access Token', ['full']);

            return $this->authService->returnAuthResponse(
                user: $user,
                tokenResult: $tokenResult,
                statusCode: Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            return $this->handleErrorResponse($th);
        }

        $user = $request->user();
        $tokenResult = $user->createToken('Full Access Token', ['full']);

        return $this->authService->returnAuthResponse(
            user: $user,
            tokenResult: $tokenResult,
            statusCode: Response::HTTP_OK
        );
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->token()->revoke();

        return $this->returnJsonResponse(
            message: 'Logged out successfully',
        );
    }

    /**
     * Handle an incoming google authentication request.
     */
    public function google(AuthProviderRequest $request): JsonResponse
    {
        $user = $this->authService->emailExist($request->email);
        if (! $user) {
            $user = $this->authService->googleSignUp($request);
        }

        $tokenResult = $user->createToken('Full Access Token', ['full']);

        return $this->authService->returnAuthResponse(
            user: $user,
            tokenResult: $tokenResult,
            statusCode: Response::HTTP_CREATED
        );
    }
}
