<?php

namespace App\Models;

use App\Enums\WalletType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Wallet extends Model
{
    use HasFactory;

    protected $table = 'wallets';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'business_id',
        'wallet_type',
        'currency_id',
        'balance',
        'wallet_name',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'wallet_type' => WalletType::class,
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
     * Get the business that owns the wallet
     */
    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    /**
     * Get the currency for this wallet
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Get the virtual account for this wallet
     */
    public function virtualAccount()
    {
        return $this->hasOne(VirtualAccount::class, 'wallet_id');
    }

    /**
     * Get all transactions for this wallet
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'wallet_id');
    }

    /**
     * Get all ledger entries for this wallet
     */
    public function ledgerEntries()
    {
        return $this->hasMany(WalletLedger::class, 'wallet_id');
    }

    /**
     * Scope a query to filter by wallet type
     */
    public function scopeByType($query, WalletType $type)
    {
        return $query->where('wallet_type', $type->value);
    }

    /**
     * Scope a query to filter by business
     */
    public function scopeByBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope a query to filter by currency
     */
    public function scopeByCurrency($query, $currencyId)
    {
        return $query->where('currency_id', $currencyId);
    }
}
