<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WalletLedger extends Model
{
    use HasFactory;

    protected $table = 'wallet_ledger';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'wallet_id',
        'transaction_id',
        'transaction_reference',
        'transaction_type',
        'amount',
        'balance_before',
        'balance_after',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'balance_before' => 'decimal:4',
        'balance_after' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    /**
     * Get the wallet for this ledger entry
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    /**
     * Get the transaction for this ledger entry
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}
