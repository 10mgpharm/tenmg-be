<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Loan extends Model
{
    use HasFactory;

    protected $table = 'credit_loans';

    protected $fillable = [
        'business_id',
        'customer_id',
        'application_id',
        'offer_id',
        'capital_amount',
        'interest_amount',
        'total_amount',
        'repaymemt_start_date',
        'repaymemt_end_date',
        'status',
        'voucher_number'
    ];

    public function repaymentSchedule()
    {
        return $this->hasMany(RepaymentSchedule::class);
    }

    public function application()
    {
        return $this->belongsTo(LoanApplication::class, 'application_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function offer()
    {
        return $this->belongsTo(CreditOffer::class, 'offer_id');
    }

    public static function boot()
    {
        parent::boot();

        self::updating(function ($model) {
            if ($model->isDirty('status') && $model->status == 'DISBURSED') {
                $businessCode = DB::table('businesses')->whereId($model->business_id)->value('code');
                $model->voucher_number = "VCH-{$businessCode}-" . time() . '-' . Str::random(5);
            }
        });

        self::creating(function ($model) {
            $businessCode = DB::table('businesses')->where('id', $model->business_id)->value('code');
            $model->identifier = "LN-{$businessCode}-" . time() . '-' . Str::random(5);
        });
    }
}
