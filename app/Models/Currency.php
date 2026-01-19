<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Currency extends Model
{
    use HasFactory;

    protected $table = 'currencies';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'classification',
        'name',
        'code',
        'symbol',
        'slug',
        'decimal_places',
        'icon',
        'description',
        'tier_1_limits',
        'tier_2_limits',
        'tier_3_limits',
        'country_code',
        'virtual_account_provider',
        'temp_virtual_account_provider',
        'virtual_card_provider',
        'bank_transfer_collection_provider',
        'mobile_money_collection_provider',
        'bank_transfer_payout_provider',
        'mobile_money_payout_provider',
        'status',
        'is_active',
    ];

    protected $casts = [
        'tier_1_limits' => 'array',
        'tier_2_limits' => 'array',
        'tier_3_limits' => 'array',
        'is_active' => 'boolean',
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
     * Get the virtual account provider
     */
    public function virtualAccountProvider()
    {
        return $this->belongsTo(ServiceProvider::class, 'virtual_account_provider');
    }

    /**
     * Get the temporary virtual account provider
     */
    public function tempVirtualAccountProvider()
    {
        return $this->belongsTo(ServiceProvider::class, 'temp_virtual_account_provider');
    }

    /**
     * Get the virtual card provider
     */
    public function virtualCardProvider()
    {
        return $this->belongsTo(ServiceProvider::class, 'virtual_card_provider');
    }

    /**
     * Get the bank transfer collection provider
     */
    public function bankTransferCollectionProvider()
    {
        return $this->belongsTo(ServiceProvider::class, 'bank_transfer_collection_provider');
    }

    /**
     * Get the mobile money collection provider
     */
    public function mobileMoneyCollectionProvider()
    {
        return $this->belongsTo(ServiceProvider::class, 'mobile_money_collection_provider');
    }

    /**
     * Get the bank transfer payout provider
     */
    public function bankTransferPayoutProvider()
    {
        return $this->belongsTo(ServiceProvider::class, 'bank_transfer_payout_provider');
    }

    /**
     * Get the mobile money payout provider
     */
    public function mobileMoneyPayoutProvider()
    {
        return $this->belongsTo(ServiceProvider::class, 'mobile_money_payout_provider');
    }

    /**
     * Get wallets for this currency
     */
    public function wallets()
    {
        return $this->hasMany(Wallet::class, 'currency_id');
    }

    /**
     * Get virtual accounts for this currency
     */
    public function virtualAccounts()
    {
        return $this->hasMany(VirtualAccount::class, 'currency_id');
    }
}
