<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AuthenticatedRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthenticatedController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(AuthenticatedRequest $request): JsonResponse
    {
        if (!$request->authenticate()) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $request->user();
        $tokenResult = $user->createToken('Full Access Token', ['full']);

        return (new UserResource($user))->additional([
            'fullAccessToken' => $tokenResult->accessToken,
            'tokenType' => 'Bearer',
            'expiresAt' => $tokenResult->token->expires_at,
            'message' => 'Sign in successful.',
        ])->response()
        ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->token()->revoke();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
