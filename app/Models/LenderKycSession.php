<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LenderKycSession extends Model
{
    use HasFactory;

    protected $table = 'lender_kyc_sessions';

    protected $fillable = [
        'lender_business_id',
        'lender_mono_profile_id',
        'prove_id',
        'reference',
        'mono_url',
        'status',
        'kyc_level',
        'bank_accounts',
        'meta',
        'completed_at',
        'verified_at',
        'completed_tier',
    ];

    protected $casts = [
        'bank_accounts' => 'boolean',
        'meta' => 'array',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function lenderBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'lender_business_id');
    }

    public function lenderMonoProfile(): BelongsTo
    {
        return $this->belongsTo(LenderMonoProfile::class, 'lender_mono_profile_id');
    }

    /**
     * Scope a query to only include completed sessions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'successful');
    }

    /**
     * Scope a query to filter by tier.
     */
    public function scopeForTier($query, string $tier)
    {
        return $query->where('kyc_level', $tier)->orWhere('completed_tier', $tier);
    }

    /**
     * Scope a query to get the latest completed session.
     */
    public function scopeLatestCompleted($query)
    {
        return $query->completed()->latest('completed_at');
    }
}
