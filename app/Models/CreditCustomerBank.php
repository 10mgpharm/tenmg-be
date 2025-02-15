<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditCustomerBank extends Model
{
    use HasFactory;

    protected $table = 'credit_customer_banks';

    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
