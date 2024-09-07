<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OtpType;
use App\Helpers\UtilityHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Auth\SignupUserRequest;
use App\Http\Resources\UserResource;

class SignupUserController extends Controller
{

    /**
     * Handle an incoming signup request.
     *
     * @param SignupUserRequest $request
     * @return JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(SignupUserRequest $request): JsonResponse
    {
        try {
            
            return DB::transaction(function () use ($request) {

                $user = $request->register();

                $otp = $user->otps()->create([
                    'code' => UtilityHelper::generateOtp(),
                    'type' => OtpType::SIGNUP_EMAIL_VERIFICATION,
                ]);
        
                $tokenResult = $user->createToken('Temporary Access Token', ['temp']);
                
                $user->sendEmailVerificationNotification();

                return (new UserResource($user))->additional([
                    'temporalAccessToken' => $tokenResult->accessToken,
                    'tokenType' => 'Bearer',
                    'expiresAt' => $tokenResult->token->expires_at,
                    'message' => 'Sign up successful. Please verify your email using the OTP sent.',
                ])->response()
                ->setStatusCode(Response::HTTP_CREATED);
            });

        } catch (\Throwable $th) {
            return response()->json( [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
                'previous' => $th->getPrevious(),
                'code' => $th->getCode(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

}
