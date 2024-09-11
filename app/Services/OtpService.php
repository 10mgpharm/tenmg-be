<?php

namespace App\Services;

use App\Models\Otp;
use App\Enums\OtpType;
use App\Helpers\UtilityHelper;
use App\Models\User;
use App\Services\Interfaces\IOtpService;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * OtpService provides methods to generate, validate, and regenerate OTPs for user authentication.
 * It handles OTP creation, expiration checks, and cleanup after use.
 */
class OtpService implements IOtpService
{
    // The number of minutes after which an OTP is considered expired.
    const TOKEN_EXPIRED_AT = 15;

    /**
     * Generates a new OTP for the authenticated user or the provided user.
     * Saves the OTP in the database and updates the existing one if already present.
     * 
     * @param OtpType $type The type of OTP to generate.
     * @param User|null $user Optional user instance, defaults to the authenticated user.
     * @return Otp The newly generated or updated OTP.
     * @throws BadRequestHttpException If no authenticated user is found.
     */
    public function generate(OtpType $type, User $user = null): Otp
    {
        $user = $user ?: request()->user();
        if (! $user) {
            throw new BadRequestHttpException('Authentication is required to proceed.');
        }

        $code = UtilityHelper::generateOtp();
        $otp = Otp::updateOrCreate(
            ['type' => $type->value, 'user_id' => $user->id],
            ['code' => $code]
        );

        return $otp;
    }

    /**
     * Validates the provided OTP code for the authenticated or provided user.
     * Deletes the OTP after successful validation.
     * 
     * @param OtpType $type The type of OTP being validated.
     * @param string $code The OTP code to validate.
     * @param User|null $user Optional user instance, defaults to the authenticated user.
     * @return Otp The validated OTP instance.
     * @throws ValidationException If the OTP has expired or is invalid.
     * @throws BadRequestHttpException If no authenticated user is found.
     */
    public function validate(OtpType $type, string $code, User $user = null): Otp
    {
        $user = $user ?: request()->user();
        if (! $user) {
            throw new BadRequestHttpException('Authentication is required to proceed.');
        }

        $otp = $user->otps()->firstWhere(['code' => $code, 'type' => $type->value]);

        if ($this->isExpired($otp)) {
            throw ValidationException::withMessages([
                'otp' => [__('The OTP provided is incorrect or has expired. Please try again.')],
            ]);
        }

        return $otp;
    }

    /**
     * Regenerates a new OTP for the specified type, deleting any existing OTP for the user.
     * 
     * @param OtpType $type The type of OTP to regenerate.
     * @param User|null $user Optional user instance, defaults to the authenticated user.
     * @return Otp The newly regenerated OTP.
     * @throws BadRequestHttpException If no authenticated user is found.
     */
    public function regenerate(OtpType $type, User $user = null): Otp
    {
        $user = $user ?: request()->user();
        if (! $user) {
            throw new BadRequestHttpException('Authentication is required to proceed.');
        }

        $otp = $user->otps()->firstWhere('type', $type->value);

        // Delete OTP since we are regenerating a new one.
        if ($otp) {
            $otp->delete();
        }

        $code = UtilityHelper::generateOtp();
        $otp = Otp::create([
            'code' => $code,
            'type' => $type->value,
            'user_id' => $user->id,
        ]);

        return $otp;
    }

    /**
     * Checks if the given OTP has expired based on the token expiration time.
     * Deletes the OTP if it has expired.
     * 
     * @param Otp|null $otp The OTP to check. Can be null.
     * @return bool True if the OTP has expired, false otherwise.
     */
    private function isExpired(Otp $otp = null): bool
    {
        if (! $otp || $otp->updated_at->diffInMinutes(now()) > self::TOKEN_EXPIRED_AT) {
            if ($otp) {
                $otp->delete(); // Delete the OTP if it exists and has expired.
            }
            return true;
        }
        return false;
    }
}
