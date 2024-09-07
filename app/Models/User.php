<?php

namespace App\Models;

use App\Notifications\Auth\ResetPasswordNotification;
use App\Notifications\Auth\VerifyEmailNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use CausesActivity, HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'avatar_id', 'id');
    }

    protected $appends = [
        'avatar',
        'status',
        'last_activity',
    ];

    protected function avatar(): Attribute
    {
        $_this = $this;

        return new Attribute(
            get: function () use ($_this) {
                return $_this->file?->url;
            }
        );
    }

    protected function status(): Attribute
    {
        $_this = $this;

        return new Attribute(
            get: function () use ($_this) {
                return $_this->active == 1 ? 'Active' : 'InActive';
            }
        );
    }

    protected function lastActivity(): Attribute
    {
        $_this = $this;

        return new Attribute(
            get: function () use ($_this) {
                $lastActivity = $_this->activities()->latest()->first();

                if (! $lastActivity) {
                    return '-';
                }

                $carbon = Carbon::parse($lastActivity->created_at);

                if ($carbon->isToday()) {
                    return 'Today, '.$carbon->format('g:i A');
                } elseif ($carbon->isYesterday()) {
                    return 'Yesterday, '.$carbon->format('g:i A');
                } else {
                    return $carbon->format('M d, Y \a\t g:i A');
                }
            }
        );
    }

    public function getAccountVerifiedAttribute()
    {
        return $this->email_verified_at ? 'Verified' : 'Not verified';
    }

    /**
     * Determine if the user has verified their email address.
     *
     * @return bool
     */
    public function hasVerifiedEmail()
    {
        return $this->email_verified_at != null;
    }

    /**
     * Mark the given user's email as verified.
     *
     * @return bool
     */
    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => now(),
        ])->save();
    }

    /**
     * sendEmailVerification
     *
     * @return void
     */
    public function sendEmailVerification(string $code)
    {
        $this->notify(new VerifyEmailNotification($code));
    }

    /**
     * Get the email address that should be used for verification.
     *
     * @return string
     */
    public function getEmailForVerification()
    {
        return $this->email;
    }

    /**
     * Get the e-mail address where password reset links are sent.
     *
     * @return string
     */
    public function getEmailForPasswordReset()
    {
        return $this->email;
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    protected function getDefaultGuardName(): string
    {
        return 'api';
    }

    /**
     * Get the otps for the user.
     */
    public function otps(): HasMany
    {
        return $this->hasMany(Otp::class)->latest();
    }
}
