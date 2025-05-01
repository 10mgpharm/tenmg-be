<?php

namespace App\Models;

use App\Helpers\UtilityHelper;
use Illuminate\Database\Eloquent\Model;

class CreditTransactionHistory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'meta' => 'array', // Cast the JSON column to an array
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->identifier = UtilityHelper::generateSlug('THG');
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');

    }

    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }
}
