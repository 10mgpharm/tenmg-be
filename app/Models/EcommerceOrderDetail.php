<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcommerceOrderDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_order_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ecommerce_order_id',
        'ecommerce_product_id',
        'supplier_id',
        'actual_price',
        'discount_price',
        'tenmg_commission',
        'quantity',
        'tenmg_commission_percent'
    ];

    /**
     * Get the order associated with the order details.
     */
    public function order()
    {
        return $this->belongsTo(EcommerceOrder::class, 'ecommerce_order_id');
    }

    /**
     * Get the product associated with the order details.
     */
    public function product()
    {
        return $this->belongsTo(EcommerceProduct::class, 'ecommerce_product_id');
    }

    /**
     * Get the user associated with the order details.
     */
    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }
}
