<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreditOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'customer_id',
        'application_id',
        'offer_amount',
        'repayment_breakdown',
        'accepted_at',
        'rejected_at',
        'rejection_reason',
        'has_mandate',
        'has_active_debit_card',
        'is_valid',
    ];

    public function application()
    {
        return $this->belongsTo(LoanApplication::class, 'application_id');
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $businessCode = DB::table('businesses')->where('id', $model->business_id)->value('code');
            $model->identifier = "LO-{$businessCode}-".time().'-'.Str::random(5);
        });
    }
}
