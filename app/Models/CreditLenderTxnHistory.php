<?php

namespace App\Models;

use App\Helpers\UtilityHelper;
use Illuminate\Database\Eloquent\Model;

class CreditLenderTxnHistory extends Model
{

    protected $guarded = [];

    public function transactionable()
    {
        return $this->morphTo();
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->identifier = UtilityHelper::generateSlug('THL');
        });
    }
}
