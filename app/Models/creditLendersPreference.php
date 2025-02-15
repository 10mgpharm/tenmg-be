<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $lender_id
 * @property array $loan_tenure
 * @property int $loan_interest
 * @property array $credit_score_category
 * @property bool $auto_accept
 */
class CreditLendersPreference extends Model
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
