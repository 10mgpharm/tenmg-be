<?php

namespace App\Models;

use App\Helpers\UtilityHelper;
use Illuminate\Database\Eloquent\Model;

class TenmgTransactionHistory extends Model
{
    protected $guarded = [];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->identifier = UtilityHelper::generateSlug('TTH');
        });
    }
}
