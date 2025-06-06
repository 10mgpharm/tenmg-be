<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreVisitorCount extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'store_visitor_counts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'count',
        'date',
    ];
}
