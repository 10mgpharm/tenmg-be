<?php

namespace App\Models;

use App\Traits\HasManyJson;
use Illuminate\Database\Eloquent\Model;

class EcommerceDiscount extends Model
{
    use HasManyJson;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_discounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'application_method',
        'coupon_code',
        'type',
        'amount',
        'applicable_products',
        'customer_limits',
        'start_date',
        'end_date',
        'business_id',
        'created_by_id',
        'updated_by_id',
        'minimum_order_amount',
        'maximum_discount_amount',

    ];

    /**
     * Get the attributes that should be cast.
     *
     * @param array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'applicable_products' => 'array',
    ];

    /**
     * Get the user that created the resource.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user that last updated the resource.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * Get the business that owns the discount.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the products associated with the resource
     */
    public function applicableProducts()
    {
        return $this->hasManyJson(EcommerceProduct::class, 'id', 'applicable_products');
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
