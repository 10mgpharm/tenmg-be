<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class MonoCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'mono_customer_id',
        'bvn_hash',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'vendor_business_id',
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
     * Find customer by BVN (using hash comparison)
     */
    public static function findByBvn(string $bvn): ?self
    {
        $customers = self::all();

        foreach ($customers as $customer) {
            if ($customer->verifyBvn($bvn)) {
                return $customer;
            }
        }

        return null;
    }

    /**
     * Get the vendor business
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'vendor_business_id');
    }

    /**
     * Get lender matches for this customer
     */
    public function lenderMatches(): HasMany
    {
        return $this->hasMany(LenderMatch::class, 'mono_customer_id');
    }
}
