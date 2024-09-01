<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationConfig extends Model
{
    use HasFactory;

    protected $table = 'integration_configs';

    protected $guarded = [];
}
