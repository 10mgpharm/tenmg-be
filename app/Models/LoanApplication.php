<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoanApplication extends Model
{
    use HasFactory;

    protected $table = 'credit_applications';

    protected $fillable = [
        'business_id',
        'customer_id',
        'requested_amount',
        'interest_amount',
        'total_amount',
        'interest_rate',
        'duration_in_months',
        'source',
        'status',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $businessCode = DB::table('businesses')->where('id', $model->business_id)->value('code');
            $model->identifier = "APP-{$businessCode}-".time().'-'.Str::random(5);
        });
    }
}
