<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Business extends Model
{
    use HasFactory;

    protected $table = 'businesses';

    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @param array<string, string>
     */
    protected $casts = [
        'expiry_date' => 'date',
    ];

    /**
     * Get the user that owns the otp.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the CAC document associated with the business.
     */
    public function cac_document(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'cac_document_id', 'id');
    }

    /**
     * Get the URL of the CAC document if available.
     */
    protected function cac(): Attribute
    {
        $_this = $this;

        return new Attribute(
            get: fn () => $_this->cac_document?->url
        );
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * Get the team members/business users associated with the business.
     */
    public function businessUsers()
    {
        return $this->hasMany(BusinessUser::class)->latest('id');
    }

    /**
     * Get the invitees associated with the business.
     */
    public function invites()
    {
        return $this->hasMany(Invite::class)->latest('id');
    }

    public function logo()
    {
        return $this->morphOne(related: FileUpload::class, name: 'model');
    }
}
