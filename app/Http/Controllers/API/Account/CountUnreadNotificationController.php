<?php

namespace App\Http\Controllers\API\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountUnreadNotificationController extends Controller
{
    /**
     * Get the total unread notifications count for the authenticated user.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->returnJsonResponse(
                    message: 'Unable to fetch unread notifications. Please try again later.'
                );
            }

            return $this->returnJsonResponse(
                message: 'Unread notifications count retrieved successfully.',
                data: ['count' => $user->unreadNotifications()->count()]
            );
        } catch (\Exception $e) {
            return $this->handleErrorResponse($e);
        }
    }
}
