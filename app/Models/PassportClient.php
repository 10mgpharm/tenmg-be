<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Passport\Client;

class PassportClient extends Client
{
    use HasFactory;
}
