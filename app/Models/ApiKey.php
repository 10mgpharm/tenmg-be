<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;

class ApiKey extends Model
{
    use HasApiTokens, HasFactory;

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
