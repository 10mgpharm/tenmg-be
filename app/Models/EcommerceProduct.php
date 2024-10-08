<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcommerceProduct extends Model
{
    use HasFactory;

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
        'ecommerce_brand_id',
        'thumbnail_file_id',
        'ecommerce_medication_type_id',
        'package_id',
        'ecommerce_variation_id',
        'created_by_id',
        'updated_by_id',
        'quantity',
        'actual_price',
        'discount_price',
        'min_delivery_duration',
        'max_delivery_duration',
        'expired_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @param array<string, string>
     */
    protected $casts = [
        'expired_at' => 'date',
    ];

}
