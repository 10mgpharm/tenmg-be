<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $table = 'credit_loans';

    public function repaymentSchedule()
    {
        return $this->hasMany(RepaymentSchedule::class);
    }
}
