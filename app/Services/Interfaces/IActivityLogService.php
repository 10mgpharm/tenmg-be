<?php

namespace App\Services\Interfaces;

use App\Models\Activity;
use App\Models\User;
use Spatie\Activitylog\LogOptions;

interface IActivityLogService
{
    public function getActivitylogOptions(): LogOptions;

    public function logActivity(mixed $model, User $causer, string $action, array $properties = [], string $logName = ''): Activity;
}
