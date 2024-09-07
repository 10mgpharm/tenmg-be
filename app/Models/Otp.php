<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Otp extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['code', 'type', 'user_id'];

    /**
     * Get the user that owns the otp.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
