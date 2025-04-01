<?php

namespace App\Services;

use App\Http\Resources\AppNotificationResource;
use App\Models\AppNotification;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Services\Interfaces\IAppNotificationSubscriptionService;
use Illuminate\Pagination\LengthAwarePaginator;

class AppNotificationSubscriptionService implements IAppNotificationSubscriptionService
{
    /**
     * Retrieve a paginated collection of notifications tailored to the user's role.
     *
     * @param User $user The authenticated user whose notifications are to be fetched.
     * @return LengthAwarePaginator Paginated list of notifications in a resource collection.
     */
    public function index(User $user): LengthAwarePaginator
    {
        
        $notifications = AppNotification::where(function ($query) use ($user) {
            $hasRole = false;

            if ($user->hasRole('admin')) {
                $query->where('is_admin', true);
                $hasRole = true;
                
            } elseif ($user->hasRole('supplier')) {
                $query->where('is_supplier', true);
                $hasRole = true;

            } elseif ($user->hasRole('pharmacy')) {
                $query->where('is_pharmacy', true);
                $hasRole = true;

            } elseif ($user->hasRole('vendor')) {
                $query->where('is_vendor', true);
                $hasRole = true;

            } elseif ($user->hasRole('lender')) {
                $query->where('is_lender', true);
                $hasRole = true;
            }

            if (!$hasRole) {
                $query->whereRaw('1 = 0'); // Ensures the query returns no results.
            }
        })
        ->with(['subscribers' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }])
        ->latest('id')
        ->paginate(request()->has('perPage') ? request()->input('perPage') : 10)
            ->withQueryString()
            ->through(fn(AppNotification $item) => AppNotificationResource::make($item));

        return ($notifications);
    }

    /**
     * Toggle the subscription status of a specified notification for the user.
     *
     * @param User $user The user toggling their subscription.
     * @param AppNotification $notification The notification to toggle subscription on.
     * @return AppNotification The updated notification instance with updated subscription status.
     */
    public function subscription(User $user, AppNotification $notification): AppNotification
    {
        if ($notification->subscribers()->where('user_id', $user->id)->exists()) {
            // Unsubscribe the user
            $notification->subscribers()->where('user_id', $user->id)->delete();
        } else {
            // Subscribe the user
            $notification->subscribers()->create(['user_id' => $user->id]);
        }

        return $notification->load('subscribers')->refresh();
    }

    /**
     * Update multiple notification subscriptions for the user based on the provided notification IDs.
     *
     * @param User $user The user updating their subscriptions.
     * @param array $notificationIds List of notification IDs the user wishes to be subscribed to.
     * @return LengthAwarePaginator Paginated list of updated notification subscriptions.
     */
    public function subscriptions(User $user, array $notificationIds): LengthAwarePaginator
    {
        NotificationSetting::where('user_id', $user->id)
            ->whereNotIn('app_notification_id', $notificationIds)
            ->delete();

        foreach ($notificationIds as $notificationId) {
            NotificationSetting::firstOrCreate([
                'user_id' => $user->id,
                'app_notification_id' => $notificationId,
            ]);
        }

        return $this->index($user);
    }
}
