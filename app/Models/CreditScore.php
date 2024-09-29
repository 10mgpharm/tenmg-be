<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreditScore extends Model
{
    use HasFactory;

    protected $table = 'credit_scores';

    protected $fillable = [
        'business_id',
        'customer_id',
        'txn_evaluation_id',
        'affordability',
        'business_rule_json',
        'credit_score_result',
        'score_percent',
        'score_value',
        'score_total',
        'created_by_id',
        'source',
    ];

    public function creditEvaluation()
    {
        return $this->belongsTo(CreditTxnHistoryEvaluation::class, 'txn_evaluation_id');
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->identifier = 'CSCORES-10MG'.time().'-'.Str::random(5);
        });
    }
}
