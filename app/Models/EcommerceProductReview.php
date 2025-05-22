<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcommerceProductReview extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_product_reviews';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ecommerce_product_id',
        'user_id',
        'name',
        'email',
        'comment',
    ];

    /**
     * Get the product the resource belongs to
     */
    public function product()
    {
        return $this->belongsTo(EcommerceProduct::class, 'ecommerce_product_id');
    }

    /**
     * Get the user that owner's the resource
     */
    public function user()
    {
        return $this->belongsTo(User::class);
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

    /**
     * Get the rating associated with the review.
     *
     * This ensures that the rating belongs to the same product and user as the review.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function rating()
    {
        return $this->hasOne(EcommerceProductRating::class, 'ecommerce_product_id', 'ecommerce_product_id')
        ->whereIn('user_id', fn ($query) =>
            $query->select('user_id')->from('ecommerce_product_reviews')
                ->whereColumn('ecommerce_product_reviews.ecommerce_product_id', 'ecommerce_product_ratings.ecommerce_product_id')
        );
    }
    
}
