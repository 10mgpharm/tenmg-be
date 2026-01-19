<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class LenderBvnLookup extends Model
{
    use HasFactory;

    protected $fillable = [
        'lender_business_id',
        'bvn_hash',
        'session_id',
        'scope',
        'status',
        'verification_method',
        'phone_number',
        'verification_methods',
        'lookup_data',
        'error_message',
    ];

    protected $casts = [
        'verification_methods' => 'array',
        'lookup_data' => 'array',
    ];

    /**
     * Hash BVN before storing
     */
    public static function hashBvn(string $bvn): string
    {
        return Hash::make($bvn);
    }

    /**
     * Check if BVN matches stored hash
     */
    public function verifyBvn(string $bvn): bool
    {
        return Hash::check($bvn, $this->bvn_hash);
    }

    /**
     * Get the lender business
     */
    public function lender(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'lender_business_id');
    }
}
