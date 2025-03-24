<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $table = 'device_tokens';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'fcm_token',
    ];

    /**
     * Get the user associated with the token.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

}
