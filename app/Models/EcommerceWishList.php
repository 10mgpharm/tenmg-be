<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcommerceWishList extends Model
{
    function product()
    {
        return $this->belongsTo(EcommerceProduct::class, 'product_id', 'id');
    }

    function customer()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
