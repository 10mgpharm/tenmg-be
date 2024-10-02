<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class VerifyTwoFactorCode implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string = null): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $google2fa = app('pragmarx.google2fa');
        $user = request()->user();

        $valid = $google2fa->verifyKey(decrypt(($user->two_factor_secret)), $value);

        if (! $valid) {
            $fail('The two-factor authentication one time password typed was wrong.');
        }
    }
}
