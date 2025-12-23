<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Business extends Model
{
    use HasFactory;

    protected $table = 'businesses';

    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @param array<string, string>
     */
    protected $casts = [
        'expiry_date' => 'date',
    ];

    /**
     * Get the user that owns the otp.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the CAC document associated with the business.
     */
    public function cac_document(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'cac_document_id', 'id');
    }

    /**
     * Get the URL of the CAC document if available.
     */
    protected function cac(): Attribute
    {
        $_this = $this;

        return new Attribute(
            get: fn () => $_this->cac_document?->url
        );
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * Get the team members/business users associated with the business.
     */
    public function businessUsers()
    {
        return $this->hasMany(BusinessUser::class)->latest('id');
    }

    /**
     * Get the invitees associated with the business.
     */
    public function invites()
    {
        return $this->hasMany(Invite::class)->latest('id');
    }

    public function logo()
    {
        return $this->morphOne(related: FileUpload::class, name: 'model');
    }

    public function wallet()
    {
        return $this->hasOne(EcommerceWallet::class, 'business_id');
    }

    public function getLenderBusinessBankAccount()
    {
        return $this->hasOne(CreditLenderBankAccounts::class, 'lender_id', 'id');
    }

    public function getLenderPreferences()
    {
        return $this->hasOne(CreditLenderPreference::class, 'lender_id', 'id');
    }

    public function lendersWallet()
    {
        return $this->hasOne(CreditLendersWallet::class, 'lender_id', 'id')->where('type', 'deposit');
    }

    public function creditLendersPreference()
    {
        return $this->hasOne(CreditLenderPreference::class, 'lender_id', 'id');
    }

    public function allLendersWallet()
    {
        return $this->hasMany(CreditLendersWallet::class, 'lender_id', 'id');
    }

    public function lendersInvestmentWallet()
    {
        return $this->hasOne(CreditLendersWallet::class, 'lender_id', 'id')->where('type', 'investment');
    }

    public function lendersLedgerWallet()
    {
        return $this->hasOne(CreditLendersWallet::class, 'lender_id', 'id')->where('type', 'ledger');
    }

    public function loanOffers()
    {
        return $this->hasMany(CreditOffer::class, 'lender_id', 'id');
    }

    public function vendorsVoucherWallet()
    {
        return $this->hasOne(CreditVendorWallets::class, 'vendor_id', 'id')->where('type', 'credit_voucher');
    }

    public function vendorsPayoutWallet()
    {
        return $this->hasOne(CreditVendorWallets::class, 'vendor_id', 'id')->where('type', 'payout');
    }

    public function apiKey()
    {
        return $this->hasOne(ApiKey::class, 'business_id', 'id');
    }

    public function lenderSetting()
    {
        return $this->hasOne(LenderSetting::class, 'business_id', 'id');
    }

    public function lenderMatchesAsVendor()
    {
        return $this->hasMany(LenderMatch::class, 'vendor_business_id', 'id');
    }

    public function lenderMatchesAsLender()
    {
        return $this->hasMany(LenderMatch::class, 'lender_business_id', 'id');
    }
}
