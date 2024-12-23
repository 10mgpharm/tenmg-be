<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EcommercePresentation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_presentations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'business_id',
        'created_by_id',
        'updated_by_id',
        'name',
        'active',
        'status',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'active' => false,
        'status' => 'PENDING',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @param array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
    ];


    /**
     * Get the products associated with this presentation
     */
    public function products()
    {
        return $this->hasMany(EcommerceProduct::class)->latest();
    }

    /**
     * Define a query scope for filtering by business_id
     */
    public function scopeBusinesses($query)
    {
        $user = request()->user();

        $business = $user->ownerBusinessType ?? $user->businesses()
            ->firstWhere('user_id', $user->id);
        return $query->where('business_id', $business?->id);
    }
}
