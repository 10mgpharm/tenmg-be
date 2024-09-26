<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DebitMandate extends Model
{
    use HasFactory;

    protected $table = 'credit_customer_debit_mandates';

    protected $fillable = [
        'business_id',
        'customer_id',
        'authorization_code',
        'active',
        'last4',
        'channel',
        'card_type',
        'bank',
        'exp_month',
        'exp_year',
        'country_code',
        'brand',
        'reusable',
        'signature',
        'account_name',
        'integration',
        'domain',
        'reference',
        'chargeable',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $businessCode = DB::table('businesses')->where('id', $model->business_id)->value('code');
            $ref = "ACCMNDT-{$businessCode}-".time().'-'.Str::random(5);
            $model->identifier = $ref;
            $model->reference = $ref;
        });
    }
}
