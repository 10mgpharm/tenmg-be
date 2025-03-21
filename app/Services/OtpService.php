<?php

namespace App\Services;

use App\Enums\MailType;
use App\Enums\OtpType;
use App\Helpers\UtilityHelper;
use App\Mail\Mailer;
use App\Models\Otp;
use App\Models\User;
use App\Services\Interfaces\IOtpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * OtpService provides methods to generate, validate, and regenerate OTPs for user authentication.
 * It handles OTP creation, expiration checks, sending emails, and cleanup after use.
 */
class OtpService implements IOtpService
{
    /**
     * The OTP expiration time in minutes.
     */
    const TOKEN_EXPIRED_AT = 15;

    /**
     * The user for whom the OTP operations are being performed.
     */
    protected ?User $user = null;

    /**
     * The generated or validated OTP instance.
     */
    protected ?Otp $otp = null;

    /**
     * Set the user for whom the OTP operations will be performed.
     *
     * @param  User|null  $user  The user instance.
     */
    public function forUser(?User $user = null): self
    {
        $this->user = $user ?: request()->user();

        return $this;
    }

    /**
     * Generate a new OTP for the specified type and user.
     * Saves the OTP in the database.
     *
     * @param  OtpType  $type  The type of OTP to generate.
     *
     * @throws BadRequestHttpException If no user is set.
     */
    public function generate(OtpType $type): self
    {
        DB::transaction(function () use ($type) {
            $user = $this->user();

            $code = UtilityHelper::generateOtp();
            $this->otp = Otp::updateOrCreate(
                ['type' => $type->value, 'user_id' => $user->id],
                ['code' => $code]
            );
        });

        return $this;
    }

    /**
     * Validate the provided OTP code for the authenticated or provided user.
     * Deletes the OTP after successful validation.
     *
     * @param  OtpType  $type  The type of OTP being validated.
     * @param  string  $code  The OTP code to validate.
     *
     * @throws ValidationException If the OTP has expired or is invalid.
     * @throws BadRequestHttpException If no user is set.
     */
    public function validate(OtpType $type, string $code): Otp
    {
        $user = $this->user();

        $this->otp = $user->otps()->firstWhere(['code' => $code, 'type' => $type->value]);

        if ($this->isExpired($this->otp)) {
            throw ValidationException::withMessages([
                'otp' => [__('The OTP provided is incorrect or has expired. Please try again.')],
            ]);
        }

        return $this->otp;
    }

    /**
     * Regenerate a new OTP for the specified type, removing the old one if it exists for the user.
     *
     * @param  OtpType  $type  The type of OTP to regenerate.
     *
     * @throws BadRequestHttpException If no user is set.
     */
    public function regenerate(OtpType $type): self
    {
        $user = $this->user();

        $otp = $user->otps()->firstWhere('type', $type->value);
        if ($otp) {
            $otp->delete();
        }

        $code = UtilityHelper::generateOtp();
        $this->otp = Otp::create([
            'code' => $code,
            'type' => $type->value,
            'user_id' => $user->id,
        ]);

        return $this;
    }

    /**
     * Send an OTP email to the user based on the OTP type.
     * If no OTP has been generated yet, an exception will be thrown.
     *
     * @param  OtpType  $type  The type of OTP (e.g., RESET_PASSWORD_VERIFICATION).
     *
     * @throws BadRequestHttpException If no OTP is available or no user is set.
     */
    public function sendMail(OtpType $type): self
    {
        $user = $this->user();

        if (! $this->otp) {
            throw new BadRequestHttpException('No OTP has been generated for this operation.');
        }

        switch ($type->value) {
            case 'RESET_PASSWORD_VERIFICATION':
                $user->sendPasswordResetNotification($this->otp->code);
                break;
            case 'SIGNUP_EMAIL_VERIFICATION':
                $user->sendEmailVerification($this->otp->code);
                break;
            case 'SUPPLIER_ADD_BANK_ACCOUNT':
                Mail::to($user->email)->send(new Mailer(MailType::SUPPLIER_ADD_BANK_ACCOUNT, ['otp' => $this->otp->code]));
                break;
        }

        return $this;
    }

    /**
     * Get the generated OTP instance.
     *
     * @return Otp|null The generated or validated OTP.
     */
    public function otp(): ?Otp
    {
        return $this->otp;
    }

    /**
     * Check if the provided OTP has expired.
     *
     * @param  Otp|null  $otp  The OTP instance to check.
     * @return bool True if expired, false otherwise.
     */
    private function isExpired(?Otp $otp = null): bool
    {
        if (! $otp || $otp->updated_at->diffInMinutes(now()) > self::TOKEN_EXPIRED_AT) {
            if ($otp) {
                $otp->delete();
            }

            return true;
        }

        return false;
    }

    /**
     * Retrieve the current user, setting it from the request if not already set.
     * Throws an exception if no user is available.
     *
     * @return User The authenticated user.
     *
     * @throws BadRequestHttpException If no user is set.
     */
    private function user(): User
    {
        $this->user = $this->user ?: request()->user();

        if (! $this->user) {
            throw new BadRequestHttpException('Authentication is required to proceed.');
        }

        return $this->user;
    }
}
