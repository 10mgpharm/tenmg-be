<?php

namespace App\Services\Interfaces;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface IAppNotificationSubscriptionService
{
    /**
     * Retrieve a paginated collection of notifications tailored to the user's role.
     *
     * Filters notifications based on the user's assigned role, such as 'admin', 'supplier',
     * 'pharmacy', or 'vendor', to display relevant notifications only. Results are paginated
     * and returned as a LengthAwarePaginator instance, wrapped in a NotificationResource collection.
     *
     * @param User $user The authenticated user whose notifications are to be fetched.
     * @return LengthAwarePaginator Paginated list of notifications in a resource collection.
     */
    public function index(User $user): LengthAwarePaginator;

    /**
     * Toggle the subscription status of a specified notification for the user.
     *
     * Checks if the user is currently subscribed to the specified notification. If subscribed,
     * the subscription is removed; otherwise, the user is subscribed to the notification.
     * Returns the updated notification with current subscribers.
     *
     * @param User $user The user toggling their subscription.
     * @param Notification $notification The notification to toggle subscription on.
     * @return Notification The updated notification instance with updated subscription status.
     */
    public function subscription(User $user, AppNotification $notification): AppNotification;

    /**
     * Update multiple notification subscriptions for the user based on the provided notification IDs.
     *
     * Removes subscriptions for notifications not in the provided list of notification IDs
     * and subscribes the user to those included in the list if not already subscribed.
     * Returns a paginated list of the updated notifications.
     *
     * @param User $user The user updating their subscriptions.
     * @param array $notificationIds List of notification IDs the user wishes to be subscribed to.
     * @return LengthAwarePaginator Paginated list of updated notification subscriptions.
     */
    public function subscriptions(User $user, array $notificationIds): LengthAwarePaginator;
}
