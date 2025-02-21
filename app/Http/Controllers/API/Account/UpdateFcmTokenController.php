<?php

namespace App\Http\Controllers\API\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateFcmTokenController extends Controller
{
    /**
     * Regenerate OTP and send it to the user's email.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->returnJsonResponse(
                    message: 'Oops, can\'t update FCM token at the moment. Please try again later.',
                );
            }

            $user->update(['fcm_token' => $request->fcm_token]);
                
            return $this->returnJsonResponse(
                message: 'FCM token saved successfully.',
            );
        } catch (\Throwable $th) {
            return $this->handleErrorResponse($th);
        }
    }
}
