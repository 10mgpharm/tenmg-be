<?php

namespace App\Models;

use App\Notifications\AdminVerificationNotification;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Notifications\Auth\VerifyEmailNotification;
use App\Notifications\LicenseVerificationNotification;
use App\Notifications\LoanSubmissionNotification;
use App\Notifications\Order\OrderConfirmationNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use CausesActivity, HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $table = 'users';

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
        'use_two_factor',
        'gender',
        'avatar_id',
        'email_verified_at',
        'google_picture_url',
        'force_password_change',
        'status',
        'status_comment',
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
            'use_two_factor' => 'boolean',
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
                return $_this->file?->url ?? $this->google_picture_url;
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
                return '-';

                $lastActivity = $_this->activities()->latest('id')->first();

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
     * Send email verification notification
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

    /**
     * Send password reset notification
     *
     * @param  string  $code
     * @return void
     */
    public function sendPasswordResetNotification($code)
    {
        $this->notify(new ResetPasswordNotification($code));
    }

    public function sendLicenseVerificationNotification($message, $user = null)
    {
        $this->notify(new LicenseVerificationNotification($message, $user));
    }

    public function sendOrderConfirmationNotification($message, $user = null)
    {
        $this->notify(new OrderConfirmationNotification($message, $user));
    }

    public function sendLoanSubmissionNotification($message, $user = null)
    {
        $this->notify(new LoanSubmissionNotification($message, $user));
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
        return $this->hasMany(Otp::class)->latest('id');
    }

    public function ownerBusinessType(): HasOne
    {
        return $this->hasOne(Business::class, 'owner_id', 'id');
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_users', 'user_id', 'business_id');
    }

    /**
     * Get the invites created by the user.
     */
    public function invites()
    {
        return $this->hasMany(Invite::class, 'creator_id')->latest('id');
    }

    /**
     * Get the invite record associated to the user.
     */
    public function invited()
    {
        return $this->belongsTo(Invite::class, 'id', 'user_id');
    }

    /**
     * Get the ecommerce medication types created by the user.
     */
    public function medicationTypes()
    {
        return $this->hasMany(EcommerceMedicationType::class, 'created_by_id')->latest('id');
    }

    /**
     * Get the ecommerce brands created by the user.
     */
    public function brands()
    {
        return $this->hasMany(EcommerceBrand::class, 'created_by_id')->latest('id');
    }

    /**
     * Get the ecommerce products created by the user.
     */
    public function products()
    {
        return $this->hasMany(EcommerceProduct::class, 'created_by_id')->latest('id');
    }

    /**
     * Get the ecommerce categories created by the user.
     */
    public function categories()
    {
        return $this->hasMany(EcommerceCategory::class, 'created_by_id')->latest('id');
    }

    /**
     * Define a query scope for filtering by business_id
     */
    public function scopeWithinBusiness($query)
    {
        $user = request()->user();

        // Get the business ID of the request user
        $business = $user->ownerBusinessType ?? $user->businesses()->first();

        // Get user IDs that belong to the same business
        $userIds = BusinessUser::where('business_id', $business?->id)->pluck('user_id');

        return $query->whereIn('id', $userIds);
    }

    /**
     * Get the user device tokens.
     */
    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class, 'user_id', 'id')->latest('id');
    }


}
