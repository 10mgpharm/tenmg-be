<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditLenderBankAccounts extends Model
{
    protected $fillable = [
        'lender_id',
        'bank_name',
        'bank_code',
        'account_name',
        'account_number',
        'bvn',
        'is_bvn_verified'
    ];
}
