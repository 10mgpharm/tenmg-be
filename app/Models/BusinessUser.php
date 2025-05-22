<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessUser extends Model
{
    use HasFactory;

    protected $table = 'business_users';

    protected $guarded = [];

    /**
     * Get the business that the business user belongs to.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the role associated with the business user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the user associated with the business user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
