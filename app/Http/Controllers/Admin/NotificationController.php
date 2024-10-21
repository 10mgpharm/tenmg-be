<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteNotificationRequest;
use App\Http\Requests\Admin\ListAllNotificationsRequest;
use App\Http\Requests\Admin\ShowNotificationRequest;
use App\Http\Requests\Admin\StoreNotificationRequest;
use App\Http\Requests\Admin\UpdateNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications.
     *
     * @return JsonResponse
     */
    public function index(ListAllNotificationsRequest $request): JsonResponse
    {
        $notifications = Notification::latest()->paginate();

        return $this->returnJsonResponse(
            message: 'Notifications successfully fetched.',
            data: NotificationResource::collection($notifications)->response()->getData(true)
        );
    }

    /**
     * Store a newly created notification in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $notification = Notification::create($validated);

        return $this->returnJsonResponse(
            message: 'Notifications created successfully.',
            data: new NotificationResource($notification),
            statusCode: Response::HTTP_CREATED,
        );
    }

    /**
     * Display the specified notification.
     *
     * @param Notification $notification
     * @return JsonResponse
     */
    public function show(ShowNotificationRequest $request, Notification $notification): JsonResponse
    {
        return $this->returnJsonResponse(
            message: 'Notification details fetched successfully.',
            data: new NotificationResource($notification),
        );
    }

    /**
     * Update the specified notification in storage.
     *
     * @param Request $request
     * @param Notification $notification
     * @return JsonResponse
     */
    public function update(UpdateNotificationRequest $request, Notification $notification): JsonResponse
    {
        $validated = $request->validated();

        $notification->update($validated);

        return response()->json([
            'message' => 'Notification updated successfully.',
            'data' => $notification,
        ]);
    }

    /**
     * Remove the specified notification from storage.
     *
     * @param Notification $notification
     * @return JsonResponse
     */
    public function destroy(DeleteNotificationRequest $request, Notification $notification): JsonResponse
    {
        $notification->delete();

        return $this->returnJsonResponse(
            message: 'Notifications deleted successfully.',
        );
    }
}
