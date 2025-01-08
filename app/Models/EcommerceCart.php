<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcommerceCart extends Model
{
    use HasFactory;

    protected $guarded = [];

    function items()
    {
        return $this->hasMany(EcommerceCartItems::class, 'cart_id', 'id');
    }
}
