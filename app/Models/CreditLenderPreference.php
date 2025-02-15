<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditLenderPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'lender_id',
        'loan_tenure',
        'loan_interest',
        'credit_score_category',
        'auto_accept',
    ];

    protected $casts = [
        'loan_tenure' => 'array',
        'credit_score_category' => 'array',
        'auto_accept' => 'boolean',
    ];
}
