<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class AuditLogService
{
    /**
     * Logs an activity to the database for administrative tracking.
     *
     * This method logs actions performed by users, such as creating, updating, or deleting resources.
     * It associates the activity with the user performing the action, the target resource, and additional
     * metadata like IP address, user agent, and business context.
     *
     * @param  Model|null  $target  The resource or model being acted upon.
     * @param  string  $event  A short description of the event (e.g., "user_updated").
     * @param  string  $action  The action being performed (e.g., "Update User Profile").
     * @param  string  $description  A detailed description of the action.
     * @param  string  $crud_type  The type of CRUD operation (e.g., "CREATE", "UPDATE", "DELETE").
     * @param  int|null  $businessId  The business ID associated with the target resource. If not provided,
     *                                it defaults to the target's business ID or the actor's business ID.
     * @param  array  $properties  Additional metadata to include in the log entry.
     * @return void
     */
    public static function log(?Model $target, string $event, string $action, string $description, string $crud_type, ?int $businessId = null, array $properties = []): void
    {
        // Use the authenticated user as the actor
        $actor = request()->user();

        // Determine the business ID for the actor (user performing the action)
        $actorBusinessId = $actor->ownerBusinessType?->id ?? $actor->businesses()->firstWhere('user_id', $actor->id)?->id;

        // Determine the business ID for the target (resource being acted upon)
        if ($target instanceof User) {
            $targetBusinessId = $target->ownerBusinessType?->id ?? $target->businesses()->firstWhere('user_id', $target->id)?->id;
        } else $targetBusinessId = $target?->business_id ?? $businessId ?? $actorBusinessId;

        // Merge default properties with custom properties
        $properties = array_merge([
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'crud_type' => $crud_type,
            'actor_business_id' => $actorBusinessId,
            'target_business_id' => $targetBusinessId,
        ], $properties);

        // Log the activity using Spatie Activitylog
        activity()
            ->causedBy($actor) // The user performing the action
            ->performedOn($target) // The resource being acted upon
            ->event($event)
            ->withProperties($properties)
            ->log($description);
    }
}
