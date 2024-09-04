<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditCustomer extends Model
{
    use HasFactory;

    protected $table = 'credit_customers';

    protected $guarded = [];
}
