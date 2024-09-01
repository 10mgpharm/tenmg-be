<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    use HasFactory;

    protected $table = 'activity_logs';

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'causer_id');
    }
}
