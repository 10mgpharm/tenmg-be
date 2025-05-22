<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepaymentLog extends Model
{
    protected $table = 'credit_repayment_logs';

    protected $fillable = [
        'business_id',
        'customer_id',
        'loan_id',
        'reference',
        'payment_method_id',
        'total_amount_paid',
        'capital_amount',
        'interest_amount',
        'penalty_fee',
        'txn_status',
        'channel',
        'channel_response',
        'channel_reference',
        'channel_fee',
    ];

    public function repayment()
    {
        return $this->belongsTo(RepaymentSchedule::class, 'payment_id');
    }
}
