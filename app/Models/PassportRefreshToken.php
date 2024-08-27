<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Passport\RefreshToken;

class PassportRefreshToken extends RefreshToken
{
    use HasFactory;
}
