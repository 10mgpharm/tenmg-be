<?php

namespace App\Services\Interfaces;

use App\Models\Otp;
use App\Enums\OtpType;
use App\Models\User;

/**
 * Interface IOtpService defines the contract for generating, regenerating, and validating OTPs.
 * It outlines the methods that must be implemented by any service providing OTP functionality.
 */
interface IOtpService
{
    /**
     * Generate a new OTP for the specified type and optionally for a specified user.
     * 
     * @param OtpType $type The type of OTP to generate (e.g., RESET_PASSWORD_VERIFICATION, SIGNUP_EMAIL_VERIFICATION).
     * @param User|null $user Optional user instance. Defaults to the authenticated user if not provided.
     * @return Otp The generated OTP instance.
     */
    public function generate(OtpType $type, User $user = null): Otp;

    /**
     * Regenerate a new OTP for the specified type, removing the old one if it exists for the user.
     * 
     * @param OtpType $type The type of OTP to regenerate (e.g., RESET_PASSWORD_VERIFICATION, SIGNUP_EMAIL_VERIFICATION).
     * @param User|null $user Optional user instance. Defaults to the authenticated user if not provided.
     * @return Otp The newly regenerated OTP instance.
     */
    public function regenerate(OtpType $type, User $user = null): Otp;

    /**
     * Validate the provided OTP code for the given OTP type and user.
     * 
     * @param OtpType $type The type of OTP being validated (e.g., RESET_PASSWORD_VERIFICATION, SIGNUP_EMAIL_VERIFICATION).
     * @param string $code The OTP code to validate.
     * @param User|null $user Optional user instance. Defaults to the authenticated user if not provided.
     * @return Otp The validated OTP instance if valid and not expired.
     * @throws \Illuminate\Validation\ValidationException If the OTP is invalid or expired.
     */
    public function validate(OtpType $type, string $code, User $user = null): Otp;
}
