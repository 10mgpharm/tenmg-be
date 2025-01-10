<?php

namespace App\Http\Controllers\API\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ListAllNotificationsRequest;
use App\Http\Requests\Account\NotificationSubscriptionRequest;
use App\Http\Requests\Account\NotificationSubscriptionsRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationSubscriptionService $notificationSubscriptionService) {}

    /**
     * Display a listing of notifications.
     */
    public function index(ListAllNotificationsRequest $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $this->notificationSubscriptionService->index($user);

        return $this->returnJsonResponse(
            message: 'Notifications successfully fetched.',
            data: $notifications
        );
    }

    /**
     * Toggle subscription for a notification.
     *
     * @param  Request  $request
     */
    public function subscription(NotificationSubscriptionRequest $request, Notification $notification): JsonResponse
    {
        $user = $request->user();
        $notification = $this->notificationSubscriptionService->subscription($user, $notification);

        if ($notification->subscribers()->where('user_id', $user->id)->exists()) {
            return $this->returnJsonResponse(
                message: 'You have successfully subscribed to the notification.',
                data: new NotificationResource($notification)
            );
        }
        return $this->returnJsonResponse(
            message: 'You have successfully unsubscribed from the notification.',
            data: new NotificationResource($notification)
        );
    }

    /**
     * subscribe and unsubscribe to notifications.
     *
     * @param  Request  $request
     */
    public function subscriptions(NotificationSubscriptionsRequest $request): JsonResponse
    {
        $user = $request->user();
        $notificationIds = $request->input('notificationIds', []);

        $notifications = $this->notificationSubscriptionService->subscriptions($user, $notificationIds);

        return $this->returnJsonResponse(
            message: 'You have successfully updated your notifications.',
            data: NotificationResource::collection($notifications)->response()->getData(true)
        );
    }
}
