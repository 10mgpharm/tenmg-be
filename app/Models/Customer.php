<?php

namespace App\Models;

use App\Helpers\UtilityHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'reference',
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
            $model->identifier = UtilityHelper::generateSlug('CUS');
        });
    }
}
