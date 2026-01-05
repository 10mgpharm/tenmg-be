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
    ];

    protected $casts = [
        'bank_accounts' => 'boolean',
        'meta' => 'array',
    ];

    public function lenderBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'lender_business_id');
    }

    public function lenderMonoProfile(): BelongsTo
    {
        return $this->belongsTo(LenderMonoProfile::class, 'lender_mono_profile_id');
    }
}
