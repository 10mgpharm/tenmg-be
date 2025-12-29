<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LenderSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'rate',
        'instruction',
        'instruction_config',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'instruction_config' => 'array',
    ];

    /**
     * Get the business that owns the lender setting.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
