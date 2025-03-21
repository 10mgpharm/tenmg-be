<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcommerceTransaction extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ecommerce_wallet_id',
        'supplier_id',
        'ecommerce_order_id',
        'txn_type',
        'txn_group',
        'amount',
        'balance_before',
        'balance_after',
        'status',
    ];

    /**
     * Get the ecommerce wallet associated with the transaction.
     */
    public function wallet()
    {
        return $this->belongsTo(EcommerceWallet::class);
    }

    /**
     * Get the user associated with the transaction.
     */
    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    /**
     * Get the ecommerce order associated with the transaction.
     */
    public function order()
    {
        return $this->belongsTo(EcommerceOrder::class, 'ecommerce_order_id');
    }
}
