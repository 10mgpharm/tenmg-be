<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(VerifyEmailRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Unauthenticated.');
        }

        $user->otps()->firstWhere('code', $request->input('otp'))->delete();

        if ($user->hasVerifiedEmail()) {
    
            $user->token()->revoke();
            $tokenResult = $user->createToken('Full Access Token', ['full']);

            return (new UserResource($user))->additional([
                'full_access_token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => $tokenResult->token->expires_at,
                'message' => 'Email already verified. Full access granted.',
            ])->response()
            ->setStatusCode(Response::HTTP_OK);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $user->token()->revoke();
        $tokenResult = $user->createToken('Full Access Token', ['full']);

        return (new UserResource($user))->additional([
            'full_access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => $tokenResult->token->expires_at,
            'message' => 'Email successfully verified. Full access granted.',
        ])->response()
        ->setStatusCode(Response::HTTP_OK);
    }
}
