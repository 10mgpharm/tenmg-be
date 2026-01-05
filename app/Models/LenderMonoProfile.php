<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class LenderMonoProfile extends Model
{
    use HasFactory;

    protected $table = 'lender_mono_profiles';

    protected $fillable = [
        'lender_business_id',
        'mono_customer_id',
        'identity_type',
        'identity_hash',
        'name',
        'email',
        'phone',
        'address',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'lender_business_id');
    }

    public static function hashIdentity(string $identity): string
    {
        return Hash::make($identity);
    }

    public function verifyIdentity(string $identity): bool
    {
        return Hash::check($identity, $this->identity_hash);
    }
}
