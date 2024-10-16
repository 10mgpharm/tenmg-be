<?php

namespace App\Http\Controllers\API\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ListAllNotificationsRequest;
use App\Http\Requests\Account\NotificationSubscriptionRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications.
     *
     * @return JsonResponse
     */
    public function index(ListAllNotificationsRequest $request): JsonResponse
    {
        $user = $request->user();

        $notifications = Notification::where(function ($query) use ($user) {
            if ($user->hasRole('admin')) {
                $query->where('is_admin', true);
            } elseif ($user->hasRole('supplier')) {
                $query->where('is_supplier', true);
            } elseif ($user->hasRole('pharmacy')) {
                $query->where('is_pharmacy', true);
            } elseif ($user->hasRole('vendor')) {
                $query->where('is_vendor', true);
            }
        })
            ->with(['subscribers' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->latest()
            ->paginate();

        return $this->returnJsonResponse(
            message: 'Notifications successfully fetched.',
            data: NotificationResource::collection($notifications)->response()->getData(true)
        );
    }


    /**
     * Toggle subscription for a notification.
     *
     * @param Request $request
     * @param Notification $notification
     * @return JsonResponse
     */
    public function subscription(NotificationSubscriptionRequest $request, Notification $notification): JsonResponse
    {
        $user = $request->user();

        // Check if the user is already subscribed to the notification
        if ($notification->subscribers()->where('user_id', $user->id)->exists()) {
            $notification->subscribers()->where('user_id', $user->id)->delete();

            return $this->returnJsonResponse(
                message: 'You have successfully unsubscribed from the notification.',
            );
        }
        
        // Subscribe the user
        $notification->subscribers()->create([
            'user_id' => $user->id,
        ]);
        
        return $this->returnJsonResponse(
            message: 'You have successfully subscribed to the notification.',
        );
    }
}
