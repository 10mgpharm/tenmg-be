<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EcommerceProduct extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_products';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'business_id',
        'thumbnail_file_id',
        'ecommerce_category_id',
        'ecommerce_brand_id',
        'ecommerce_medication_type_id',
        'ecommerce_measurement_id',
        'ecommerce_presentation_id',
        'ecommerce_package_id',
        'ecommerce_variation_id',
        'created_by_id',
        'updated_by_id',
        'quantity',
        'actual_price',
        'discount_price',
        // 'min_delivery_duration',
        // 'max_delivery_duration',
        'expired_at',
        'commission',
        'status',
        'name',
        'slug',
        'low_stock_level',
        'out_stock_level',
        'description',
        'active',
        'weight',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @param array<string, string>
     */
    protected $casts = [
        'expired_at' => 'date',
    ];

    /**
     * Get the category associated with the product.
     */
    public function category()
    {
        return $this->belongsTo(EcommerceCategory::class, 'ecommerce_category_id');
    }

    /**
     * Get the branch associated with the product.
     */
    public function brand()
    {
        return $this->belongsTo(EcommerceBrand::class, 'ecommerce_brand_id');
    }

    /**
     * Get the medication type associated with the product.
     */
    public function medicationType()
    {
        return $this->belongsTo(EcommerceMedicationType::class, 'ecommerce_medication_type_id');
    }

    /**
     * Get the thumbnail file associated with the product.
     */
    public function thumbnailFile()
    {
        return $this->belongsTo(FileUpload::class, 'thumbnail_file_id');
    }

    /**
     * Get the URL of the thumbnail.
     */
    public function thumbnailUrl(): Attribute
    {
        return new Attribute(
            get: fn () => $this->thumbnailFile?->url
        );
    }

    /**
     * Get the variation associated with the product.
     */
    public function variation()
    {
        return $this->belongsTo(EcommerceMedicationVariation::class, 'ecommerce_variation_id');
    }


    /**
     * Get the presentation associated with the product.
     */
    public function presentation()
    {
        return $this->belongsTo(EcommercePresentation::class, 'ecommerce_presentation_id');
    }

    /**
     * Get the measurement associated with the product.
     */
    public function measurement()
    {
        return $this->belongsTo(EcommerceMeasurement::class, 'ecommerce_measurement_id');
    }

    /**
     * Get the measurement associated with the product.
     */
    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    /**
     * Get the product details associated with the product.
     */
    public function productDetails()
    {
        return $this->hasOne(EcommerceProductDetail::class, 'ecommerce_product_id');
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
