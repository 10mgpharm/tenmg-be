<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AuthenticatedRequest;
use App\Services\Interfaces\IAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
        if (! $request->authenticate()) {
            return response()->json([
                'message' => 'Email or Password is invalid',
                'status' => 'error',
            ], Response::HTTP_UNAUTHORIZED);
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
     * Email exist check
     */
    public function emailExist(Request $request): JsonResponse
    {
        $user = $this->authService->emailExist($request->input('email'));

        return $this->returnJsonResponse(
            data: (bool) ($user)
        );
    }
}
