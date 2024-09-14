<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepaymentSchedule extends Model
{
    use HasFactory;

    protected $table = 'credit_repayment_schedules';

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'loan_id', 'id'); // Assuming 'id' is the primary key of the Loan model
    }
}
