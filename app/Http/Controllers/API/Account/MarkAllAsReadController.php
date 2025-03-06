<?php

namespace App\Http\Controllers\API\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarkAllAsReadController extends Controller
{
    /**
     * Mark all unread notifications as read for the authenticated user.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->unreadNotifications->markAsRead();
        
            return $this->returnJsonResponse(
                message: 'All unread notifications have been marked as read.'
            );
        } catch (\Throwable $th) {
            return $this->handleErrorResponse($th);
        }
    }
}
