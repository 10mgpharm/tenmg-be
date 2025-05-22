<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcommerceCartItems extends Model
{
    //
    protected $guarded = [];

    function product()
    {
        return $this->belongsTo(EcommerceProduct::class, 'product_id', 'id');
    }
}
