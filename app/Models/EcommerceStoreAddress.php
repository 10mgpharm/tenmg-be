<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcommerceStoreAddress extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_store_addresses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'business_id',
        'country',
        'state',
        'city',
        'closest_landmark',
        'street_address',
    ];

    /**
     * Get the business associated with the wallet.
     */
    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

}
