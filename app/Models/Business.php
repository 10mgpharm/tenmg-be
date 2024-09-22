<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Business extends Model
{
    use HasFactory;

    protected $table = 'businesses';

    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @param array<string, string>
     */
    protected $casts = [
        'expiry_date' => 'date',
    ];

    /**
     * Get the user that owns the otp.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
