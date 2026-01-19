<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ServiceProvider extends Model
{
    use HasFactory;

    protected $table = 'service_providers';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'config',
        'metadata',
        'is_bvn_verification_provider',
        'is_virtual_account_provider',
        'is_virtual_card_provider',
        'is_physical_card_provider',
        'is_checkout_provider',
        'is_bank_payout_provider',
        'is_mobile_money_payout_provider',
        'is_identity_verification_provider',
        'currencies_supported',
        'status',
    ];

    protected $casts = [
        'config' => 'array',
        'metadata' => 'array',
        'currencies_supported' => 'array',
        'is_bvn_verification_provider' => 'boolean',
        'is_virtual_account_provider' => 'boolean',
        'is_virtual_card_provider' => 'boolean',
        'is_physical_card_provider' => 'boolean',
        'is_checkout_provider' => 'boolean',
        'is_bank_payout_provider' => 'boolean',
        'is_mobile_money_payout_provider' => 'boolean',
        'is_identity_verification_provider' => 'boolean',
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
     * Get currencies that use this provider for virtual accounts
     */
    public function currenciesForVirtualAccounts()
    {
        return $this->hasMany(Currency::class, 'virtual_account_provider');
    }

    /**
     * Get currencies that use this provider for temporary virtual accounts
     */
    public function currenciesForTempVirtualAccounts()
    {
        return $this->hasMany(Currency::class, 'temp_virtual_account_provider');
    }

    /**
     * Get currencies that use this provider for virtual cards
     */
    public function currenciesForVirtualCards()
    {
        return $this->hasMany(Currency::class, 'virtual_card_provider');
    }

    /**
     * Get currencies that use this provider for bank transfer collections
     */
    public function currenciesForBankTransferCollections()
    {
        return $this->hasMany(Currency::class, 'bank_transfer_collection_provider');
    }

    /**
     * Get currencies that use this provider for mobile money collections
     */
    public function currenciesForMobileMoneyCollections()
    {
        return $this->hasMany(Currency::class, 'mobile_money_collection_provider');
    }

    /**
     * Get currencies that use this provider for bank transfer payouts
     */
    public function currenciesForBankTransferPayouts()
    {
        return $this->hasMany(Currency::class, 'bank_transfer_payout_provider');
    }

    /**
     * Get currencies that use this provider for mobile money payouts
     */
    public function currenciesForMobileMoneyPayouts()
    {
        return $this->hasMany(Currency::class, 'mobile_money_payout_provider');
    }

    /**
     * Get virtual accounts using this provider
     */
    public function virtualAccounts()
    {
        return $this->hasMany(VirtualAccount::class, 'provider');
    }

    /**
     * Get transactions processed by this provider
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'processor');
    }
}
