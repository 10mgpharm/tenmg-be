<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcommerceBankAccount extends Model
{
    use HasFactory;


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_bank_accounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_id',
        'bank_name',
        'bank_code',
        'account_name',
        'account_number',
        'active',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'active' => 'active',
    ];

    /**
     * Get the user associated with the bank account.
     */
    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

}
