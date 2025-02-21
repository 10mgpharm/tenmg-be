<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'app_notifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'is_admin',
        'is_supplier',
        'is_pharmacy',
        'is_vendor',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'is_admin' => 'boolean',
        'is_supplier' => 'boolean',
        'is_pharmacy' => 'boolean',
        'is_vendor' => 'boolean',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, bool>
     */
    protected $attributes = [
        'active' => false,
        'is_admin' => false,
        'is_supplier' => false,
        'is_pharmacy' => false,
        'is_vendor' => false,
    ];

    /**
     * Get the notification settings associated with the notification.
     */
    public function subscribers()
    {
        return $this->hasMany(NotificationSetting::class);
    }
}
