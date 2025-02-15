<?php

namespace App\Models;

use App\Helpers\UtilityHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DebitMandate extends Model
{
    use HasFactory;

    protected $table = 'credit_fincra_debit_mandates';

    protected $guard = [];

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
            $ref = UtilityHelper::generateSlug('MND');
            $model->identifier = $ref;
        });
    }
}
