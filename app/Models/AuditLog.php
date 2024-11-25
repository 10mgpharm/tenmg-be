<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event',
        'action',
        'description',
        'company_id',
        'ip_address',
        'crud_type',
        'user_agent',
        'creatable_type',
        'creatable_id',
        'targetable_type',
        'targetable_id',
    ];

    protected $hidden = [
        'creatable_type', 'creatable_id', 'targetable_type', 'targetable_id'
    ];

    /**
     * Get the creator of the admin log (morph relation).
     */
    public function creatable()
    {
        return $this->morphTo();
    }

    /**
     * Get the target of the admin log (morph relation).
     */
    public function targetable()
    {
        return $this->morphTo();
    }
}
