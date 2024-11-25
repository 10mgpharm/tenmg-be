<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditTxnHistoryEvaluation extends Model
{
    use HasFactory;

    protected $table = 'credit_txn_history_evaluations';

    protected $guarded = [];

    public function creditScore()
    {
        return $this->hasOne(CreditScore::class, 'txn_evaluation_id');
    }

    public function customerRecord()
    {
        return $this->hasOne(User::class, 'id', 'customer_id');
    }
}
