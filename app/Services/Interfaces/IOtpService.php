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
     * Set the user for the OTP operations.
     * 
     * @param User $user
     * @return $this
     */
    public function forUser(User $user): self;

    /**
     * Generate a new OTP for the specified type and the user set by forUser().
     * 
     * @param OtpType $type The type of OTP to generate (e.g., RESET_PASSWORD_VERIFICATION, SIGNUP_EMAIL_VERIFICATION).
     * @return $this
     */
    public function generate(OtpType $type): self;

    /**
     * Get the generated OTP instance.
     * 
     * @return Otp|null The generated OTP or null if no OTP has been generated yet.
     */
    public function otp(): ?Otp;

    /**
     * Regenerate a new OTP for the specified type, removing the old one if it exists for the user set by forUser().
     * 
     * @param OtpType $type The type of OTP to regenerate (e.g., RESET_PASSWORD_VERIFICATION, SIGNUP_EMAIL_VERIFICATION).
     * @return $this
     */
    public function regenerate(OtpType $type): self;

    /**
     * Validate the provided OTP code for the given OTP type and the user set by forUser().
     * 
     * @param OtpType $type The type of OTP being validated (e.g., RESET_PASSWORD_VERIFICATION, SIGNUP_EMAIL_VERIFICATION).
     * @param string $code The OTP code to validate.
     * @return Otp The validated OTP instance if valid and not expired.
     * @throws \Illuminate\Validation\ValidationException If the OTP is invalid or expired.
     */
    public function validate(OtpType $type, string $code): Otp;

    /**
     * Send the OTP via email for the user set by forUser().
     * 
     * @param OtpType $type The type of OTP (e.g., RESET_PASSWORD_VERIFICATION).
     * @return $this
     */
    public function sendMail(OtpType $otpType): self;
}
