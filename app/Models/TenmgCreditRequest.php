<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenmgCreditRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'payload',
        'status',
        'sdk_url',
        'initiated_by',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
