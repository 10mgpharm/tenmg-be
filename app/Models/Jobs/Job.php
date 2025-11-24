<?php

namespace App\Models\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    protected $table = 'job_listings';

    protected $fillable = [
        'title',
        'slug',
        'department',
        'employment_type',
        'mission',
        'responsibilities',
        'requirements',
        'compensation',
        'flexibility',
        'how_to_apply',
        'apply_url',
        'location_type',
        'about_company',
        'status',
    ];

    protected $casts = [
        'employment_type' => 'array',
        'requirements' => 'array',
    ];
}
