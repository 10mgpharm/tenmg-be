<?php

namespace App\Http\Controllers\API\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
    
        $notifications = $user->notifications()->latest('id')
        ->paginate(request()->has('perPage') ? request()->input('perPage') : 10)
            ->withQueryString()
            ->through(fn(DatabaseNotification $item) => NotificationResource::make($item));

        return $this->returnJsonResponse(
            message: 'Notifications successfully fetched.',
            data: $notifications
        );
    }


    /**
     * Display the specified resource.
     */
    public function show(DatabaseNotification $notification)
    {
        return $this->returnJsonResponse(
            message: 'Notification details fetched successfully.',
            data: new NotificationResource($notification),
        );
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DatabaseNotification $notification)
    {
        if($notification->read_at) {
            return $this->returnJsonResponse(
                message: 'Notification already marked as read.',
                data: new NotificationResource($notification),
            );
        }

        $notification->update(['read_at' => now()]);

        return $this->returnJsonResponse(
            message: 'Notification marked as read.',
            data: new NotificationResource($notification),
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, DatabaseNotification $notification)
    {
        $notification->delete();

        return $this->returnJsonResponse(
            message: 'Notification deleted successfully.',
        );
    }
}
