<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'credit_customers';

    protected $fillable = [
        'business_id',
        'name',
        'email',
        'phone',
        'identifier',
        'active',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function debitMandate()
    {
        return $this->hasOne(DebitMandate::class);
    }

    public function avatar()
    {
        return $this->morphOne(FileUpload::class, 'model');
    }

    public function evaluationHistories()
    {
        return $this->hasMany(CreditTxnHistoryEvaluation::class);
    }

    public function lastEvaluationHistory()
    {
        return $this->hasOne(CreditTxnHistoryEvaluation::class)->latest();
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $businessCode = DB::table('businesses')->where('id', $model->business_id)->value('code');
            $model->identifier = strtoupper($businessCode) . '-CUS-' . time() . '-' . Str::random(5);
        });
    }
}
