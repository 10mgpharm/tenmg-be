<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditLendersWallet extends Model
{
    use HasFactory;

    protected $table = 'credit_lenders_wallets';

    protected $fillable = [
        'lender_id',
        'type',
        'prev_balance',
        'current_balance',
        'last_transaction_ref'
    ];
}
