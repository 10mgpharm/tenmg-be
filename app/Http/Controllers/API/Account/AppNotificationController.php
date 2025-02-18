<?php

namespace App\Http\Controllers\API\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ListAllNotificationsRequest;
use App\Http\Requests\Account\NotificationSubscriptionRequest;
use App\Http\Requests\Account\NotificationSubscriptionsRequest;
use App\Http\Resources\AppNotificationResource;
use App\Models\AppNotification;
use App\Services\AppNotificationSubscriptionService;
use App\Services\NotificationSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppNotificationController extends Controller
{
    public function __construct(private AppNotificationSubscriptionService $notificationSubscriptionService) {}

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
    public function subscription(NotificationSubscriptionRequest $request, AppNotification $notification): JsonResponse
    {
        $user = $request->user();
        $notification = $this->notificationSubscriptionService->subscription($user, $notification);

        if ($notification->subscribers()->where('user_id', $user->id)->exists()) {
            return $this->returnJsonResponse(
                message: 'You have successfully subscribed to the notification.',
                data: new AppNotificationResource($notification)
            );
        }
        return $this->returnJsonResponse(
            message: 'You have successfully unsubscribed from the notification.',
            data: new AppNotificationResource($notification)
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
            data: AppNotificationResource::collection($notifications)->response()->getData(true)
        );
    }
}
