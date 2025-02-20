<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteAppNotificationRequest;
use App\Http\Requests\Admin\DeleteNotificationRequest;
use App\Http\Requests\Admin\ListAllAppNotificationsRequest;
use App\Http\Requests\Admin\ShowAppNotificationRequest;
use App\Http\Requests\Admin\StoreAppNotificationRequest;
use App\Http\Requests\Admin\UpdateAppNotificationRequest;
use App\Http\Resources\AppNotificationResource;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AppNotificationController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index(ListAllAppNotificationsRequest $request): JsonResponse
    {
        $notifications = AppNotification::latest()->paginate();

        return $this->returnJsonResponse(
            message: 'Notifications successfully fetched.',
            data: AppNotificationResource::collection($notifications)->response()->getData(true)
        );
    }

    /**
     * Store a newly created notification in storage.
     *
     * @param  Request  $request
     */
    public function store(StoreAppNotificationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $notification = AppNotification::create($validated);

        return $this->returnJsonResponse(
            message: 'Notifications created successfully.',
            data: new AppNotificationResource($notification),
            statusCode: Response::HTTP_CREATED,
        );
    }

    /**
     * Display the specified notification.
     */
    public function show(ShowAppNotificationRequest $request, AppNotification $notification): JsonResponse
    {
        return $this->returnJsonResponse(
            message: 'Notification details fetched successfully.',
            data: new AppNotificationResource($notification),
        );
    }

    /**
     * Update the specified notification in storage.
     *
     * @param  Request  $request
     */
    public function update(UpdateAppNotificationRequest $request, AppNotification $notification): JsonResponse
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
     */
    public function destroy(DeleteAppNotificationRequest $request, AppNotification $notification): JsonResponse
    {
        $notification->delete();

        return $this->returnJsonResponse(
            message: 'Notifications deleted successfully.',
        );
    }
}
