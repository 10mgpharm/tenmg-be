<?php

namespace App\Models;

use App\Helpers\UtilityHelper;
use Illuminate\Database\Eloquent\Model;

class CreditRepaymentPayments extends Model
{
    protected $table = 'credit_repayment_payments';
    protected $guarded = [];

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->reference = UtilityHelper::generateSlug('LNR');
        });
    }
}
