<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcommerceWallet extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_wallets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'business_id',
        'previous_balance',
        'current_balance',
    ];

    /**
     * Get the business associated with the wallet.
     */
    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    /**
     * Get the bank account associated with the wallet.
     */
    public function bankAccount()
    {
        return $this->hasOne(EcommerceBankAccount::class, 'supplier_id', 'business_id');
    }

    /**
     * Get the transactions associated with the wallet.
     */
    public function transactions()
    {
        return $this->morphMany(EcommerceTransaction::class, 'walletable');
    }
}
