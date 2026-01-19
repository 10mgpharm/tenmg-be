<?php

namespace App\Models;

use App\Enums\VirtualAccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VirtualAccount extends Model
{
    use HasFactory;

    protected $table = 'virtual_accounts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'business_id',
        'currency_id',
        'wallet_id',
        'type',
        'provider',
        'provider_reference',
        'provider_status',
        'account_name',
        'bank_name',
        'account_number',
        'account_type',
        'bank_code',
        'routing_number',
        'country_code',
        'iban',
        'check_number',
        'sort_code',
        'bank_swift_code',
        'addressable_in',
        'bank_address',
        'status',
    ];

    protected $casts = [
        'type' => VirtualAccountType::class,
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
     * Get the business that owns the virtual account
     */
    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    /**
     * Get the wallet associated with this virtual account
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    /**
     * Get the currency for this virtual account
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Get the service provider for this virtual account
     */
    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class, 'provider');
    }
}
