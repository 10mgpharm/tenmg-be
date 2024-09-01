<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission as SpattiePermission;

class Permission extends SpattiePermission
{
    use HasFactory, SoftDeletes;

    protected $table = 'permissions';

    protected static function boot()
    {
        parent::boot();
    }

    public function permission_group()
    {
        return $this->belongsTo(PermissionGroup::class);
    }

    public function scopeWhereGroup($query, $group)
    {
        $query->whereExists(function ($query) use ($group) {
            $query->select(DB::raw(1))
                ->from('permission_groups')
                ->where('id', $group)
                ->whereColumn('permission_groups.id', 'permissions.permission_group_id');
        });
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query
                ->join('permission_groups', 'permission_groups.id', '=', 'permissions.permission_group_id')
                ->where(function ($query) use ($search) {
                    $query->where('permission_groups.name', 'like', '%'.$search.'%')
                        ->orWhere('permissions.name', 'like', '%'.$search.'%');
                });
        })->when($filters['group'] ?? null, function ($query, $group) {
            $query->whereGroup($group);
        });
    }
}
