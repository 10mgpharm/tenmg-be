<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'credit_customers';

    protected $fillable = [
        'business_id',
        'name',
        'email',
        'phone',
        'identifier',
        'active',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function avatar()
    {
        return $this->morphOne(FileUpload::class, 'model');
    }
}
