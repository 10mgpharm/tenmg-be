<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpattieRole;

class Role extends SpattieRole
{
    use HasFactory;

    protected $table = 'roles';

    protected static function boot()
    {
        parent::boot();
    }
}
