<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LenderMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_business_id',
        'lender_business_id',
        'amount',
        'currency',
        'default_tenor',
        'borrower_reference',
        'transaction_history',
        'product_items',
        'callback_url',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_history' => 'array',
        'product_items' => 'array',
    ];

    /**
     * Get the vendor business that made this match request.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'vendor_business_id');
    }

    /**
     * Get the lender business that was matched.
     */
    public function lender(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'lender_business_id');
    }
}
