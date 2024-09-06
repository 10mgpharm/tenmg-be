<?php

namespace App\Helpers;

class UtilityHelper
{
    /**
     * Generate a strong and unique 6-character OTP.
     *
     * The OTP consists of 3 random digits and 3 random uppercase letters, 
     * shuffled together to ensure randomness. The digits are selected from 
     * 0-9, and the letters are selected from A-Z.
     *
     * @return string The generated OTP.
     *
     * @throws \Exception If it was not possible to gather sufficient entropy.
     */
    public static function generateOtp(): string
    {
        $numbers = array_map(fn() => random_int(0, 9), range(1, 3));
        $letters = array_map(fn() => chr(random_int(65, 90)), range(1, 3));

        $shuffled = array_merge($numbers, $letters);
        shuffle($shuffled);

        return implode($shuffled);
    }

}
