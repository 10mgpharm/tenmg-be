<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notification_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'notification_id',
        'user_id',
    ];

    /**
     * Get the notification associated with the notification system.
     */
    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * Get the user associated with the notification system.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
