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
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cac_document(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'cac_document_id', 'id');
    }

    /**
     * Get the URL of the CAC document if available.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function cac(): Attribute
    {
        $_this = $this;

        return new Attribute(
            get: fn() => $_this->cac_document?->url
        );
    }
    
    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * Get the team members associated with the business.
     */
    public function teamMembers()
    {
        return $this->hasMany(TeamMember::class);
    }
}
