<?php

namespace App\Http\Controllers\API\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreFcmTokenRequest;
use Illuminate\Http\JsonResponse;

class UpdateFcmTokenController extends Controller
{
    /**
     * Regenerate OTP and send it to the user's email.
     */
    public function __invoke(StoreFcmTokenRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $validated = $request->validated();


            if (!$user) {
                return $this->returnJsonResponse(
                    message: 'Oops, can\'t update FCM token at the moment. Please try again later.',
                );
            }

            $user->deviceTokens()->updateOrCreate($validated);
                
            return $this->returnJsonResponse(
                message: 'FCM token saved successfully.',
            );
        } catch (\Exception $e) {
            return $this->handleErrorResponse($e);
        }
    }
}
