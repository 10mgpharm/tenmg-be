<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class LogService
{
    /**
     * Logs to database for admins
     *
     * @param  Model  $creatable  The user who is performing the action.
     * @param  Model|null  $targetable  The resource/model the user performed action on.
     * @param  string  $event  Short event of action being carried out.
     * @param  string  $action  Action being carried out.
     * @param  string  $description  Description of action being carried out.
     * @param  string  $crud_type  Action type in UPDATE, CREATE or DELETE
     * @param  int|null  $company_id  Auth user company id
     */
    public static function log(Model $creatable, ?Model $targetable, string $event, string $action, string $description, string $crud_type)
    {
        AuditLog::create([
            'creatable_type' => get_class($creatable),
            'creatable_id' => $creatable->id,
            'targetable_type' => $targetable ? get_class($targetable) : null,
            'targetable_id' => $targetable ? $targetable->id : null,
            'event' => $event,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'crud_type' => $crud_type,
            'creatable_business_id' => $creatable->ownerBusinessType?->id ?? $creatable->businesses()->firstWhere('user_id', $creatable->id)?->id,
            'targetable_business_id' => $targetable?->ownerBusinessType?->id ?? $targetable?->businesses()->firstWhere('user_id', $targetable?->id)?->id,
        ]);
    }
}
