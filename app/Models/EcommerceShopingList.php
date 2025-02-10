<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcommerceShopingList extends Model
{
    //
    function attachment()
    {
        return $this->belongsTo(FileUpload::class, 'shopping_list_image_id', 'id');

    }

    function product()
    {
        return $this->belongsTo(EcommerceProduct::class, 'product_id', 'id');
    }
}
