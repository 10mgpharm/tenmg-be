<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Invite extends Model
{
    use HasFactory, Notifiable;

    protected $table = 'invites';

    protected $fillable = [
        'full_name',
        'email',
        'status',
        'business_id',
        'role_id',
        'creator_id',
        'expires_at',
        'invite_token',
        'user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @param array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that created the team member.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the business that the team member belongs to.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the role associated with the invitee.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the user associated with the invitee.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
