<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenMgWallet extends Model
{
    protected $guarded = [];

    /**
     * Get the ecommerce transactions associated with the wallet.
     */
    public function ecommerceTransactions()
    {
        return $this->morphMany(EcommerceTransaction::class, 'walletable');
    }
}
