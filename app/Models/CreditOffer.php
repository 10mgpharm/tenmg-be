<?php

namespace App\Models;

use App\Helpers\UtilityHelper;
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
        'lender_id'
    ];

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

    public function loans()
    {
        return $this->belongsTo(Loan::class, 'offer_id');
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->identifier = UtilityHelper::generateSlug('LO');
        });
    }
}
