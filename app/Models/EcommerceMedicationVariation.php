<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EcommerceMedicationVariation extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_medication_variations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'business_id',
        'created_by_id',
        'updated_by_id',
        'ecommerce_medication_type_id',
        'strength_value',
        'strength',
        // 'presentation',
        'ecommerce_presentation_id',
        // 'package_per_roll',
        'ecommerce_package_id',
        'description',
        'weight',
        'status',
        'active'
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
     * Get the products associated with this medication variation
     */
    public function products()
    {
        return $this->hasMany(EcommerceProduct::class, 'ecommerce_variation_id')->latest();
    }

    /**
     * Get the presentation associated with the medication variation.
     */
    public function presentation()
    {
        return $this->belongsTo(EcommercePresentation::class, 'ecommerce_presentation_id');
    }

    /**
     * Get the package associated with the medication variation.
     */
    public function package()
    {
        return $this->belongsTo(EcommercePackage::class, 'ecommerce_package_id');
    }

    /**
     * Get the medication type associated with the variation.
     */
    public function medicationType()
    {
        return $this->belongsTo(EcommerceMedicationType::class, 'ecommerce_medication_type_id');
    }

    /**
     * Get the business associated with the variation.
     */
    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
