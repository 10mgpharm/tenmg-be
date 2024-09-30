<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'email',
        'status',
        'business_id',
        'creator_id',
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
}
