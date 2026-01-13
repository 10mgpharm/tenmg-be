<?php

namespace App\Models;

use App\Enums\TransactionCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'business_id',
        'wallet_id',
        'currency_id',
        'transaction_category',
        'transaction_type',
        'transaction_method',
        'transaction_reference',
        'transaction_narration',
        'transaction_description',
        'amount',
        'processor',
        'processor_reference',
        'beneficiary_id',
        'status',
        'balance_before',
        'balance_after',
        'transaction_data',
    ];

    protected $casts = [
        'transaction_category' => TransactionCategory::class,
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_data' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the business that owns the transaction
     */
    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    /**
     * Get the wallet for this transaction
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    /**
     * Get the currency for this transaction
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Get the service provider (processor) for this transaction
     */
    public function processor()
    {
        return $this->belongsTo(ServiceProvider::class, 'processor');
    }

    /**
     * Get the ledger entry for this transaction
     */
    public function ledgerEntry()
    {
        return $this->hasOne(WalletLedger::class, 'transaction_id');
    }

    /**
     * Scope a query to filter by transaction category
     */
    public function scopeByCategory($query, TransactionCategory $category)
    {
        return $query->where('transaction_category', $category->value);
    }

    /**
     * Scope a query to filter by transaction type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope a query to filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by business
     */
    public function scopeByBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
