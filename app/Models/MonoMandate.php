<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonoMandate extends Model
{
    use HasFactory;

    protected $fillable = [
        'lender_match_id',
        'mono_customer_id',
        'mandate_id',
        'reference',
        'mono_url',
        'status',
        'amount',
        'currency',
        'start_date',
        'end_date',
        'description',
        'redirect_url',
        'meta',
        'mono_response',
        'is_mock',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'meta' => 'array',
        'mono_response' => 'array',
        'is_mock' => 'boolean',
    ];

    /**
     * Get the lender match for this mandate.
     */
    public function lenderMatch(): BelongsTo
    {
        return $this->belongsTo(LenderMatch::class);
    }

    /**
     * Get the Mono customer for this mandate.
     */
    public function monoCustomer(): BelongsTo
    {
        return $this->belongsTo(MonoCustomer::class);
    }
}
