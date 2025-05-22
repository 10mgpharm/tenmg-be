<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use App\Services\Interfaces\IActivityLogService;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ActivityLogService implements IActivityLogService
{
    use LogsActivity;

    protected static $logName = 'user';

    protected static $logOnlyDirty = true;

    protected static $logAttributes = ['name', 'email', 'phone', 'active'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'active'])
            ->logOnlyDirty()
            ->useLogName('user');
    }

    /**
     * Log activity for a given model and user.
     *
     * @param  mixed  $causer
     */
    public function logActivity(mixed $model, User $causer, string $action, array $properties = [], string $logName = ''): Activity
    {
        return activity($logName)
            ->performedOn($model)
            ->causedBy($causer)
            ->withProperties($properties)
            ->log($action);
    }
}
