<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcommercePayout extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_payouts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'initiated_by_id',
        'recipient_business_id',
        'recipient_bank_id',
        'reference',
        'txn_id',
        'payout_type',
        'channel',
        'status',
        'amount_sent',
        'channel_fee',
        'channel_response',
        'channel_reference',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'PENDING',
    ];

    /**
     * Get the user associated with the payout.
     */
    public function initiatedBy()
    {
        return $this->belongsTo(User::class, 'initiated_by_id');
    }

    /**
     * Get the business associated with the payoout.
     */
    public function recipientBusiness()
    {
        return $this->belongsTo(Business::class, 'recipient_business_id');
    }

    /**
     * Get the bank account associated with the paylout.
     */
    public function recipientBank()
    {
        return $this->belongsTo(EcommerceBankAccount::class, 'recipient_bank_id');
    }
}
