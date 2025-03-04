<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcommerceOrder extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'ecommerce_payment_method_id',
        'qty_total',
        'order_total',
        'grand_total',
        'logistic_total',
        'total_weight',
        'delivery_address',
        'delivery_type',
        'status',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'PENDING',
    ];

    /**
     * Get the customer associated with the order.
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id', 'id'); //
    }

    /**
     * Get the payment method associated with the order.
     */
    public function paymentMethod()
    {
        return $this->belongsTo(EcommercePaymentMethod::class, 'ecommerce_payment_method_id');
    }

    /**
     * Get the order details associated with the order.
     */
    public function orderDetails()
    {
        return $this->hasMany(EcommerceOrderDetail::class);
    }
}
