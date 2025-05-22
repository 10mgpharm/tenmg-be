<?php

namespace App\Http\Controllers\API\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreFcmTokenRequest;
use App\Models\DeviceToken;
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

            // Delete any existing token if it belongs to a different user
            DeviceToken::where('fcm_token', $validated['fcm_token'])->where('user_id', '!=', $user->id)->delete();

            // Add or update the token for the correct user
            $user->deviceTokens()->updateOrCreate($validated);
                
            return $this->returnJsonResponse(
                message: 'FCM token saved successfully.',
            );
        } catch (\Exception $e) {
            return $this->handleErrorResponse($e);
        }
    }
}
