<?php

namespace App\Rules;

use App\Constants\PublicDomainConstants;
use App\Enums\BusinessType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BusinessEmail implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domain = substr(strrchr($value, '@'), 1);

        // Check if the domain is public and the business type is 'vendor'
        if (
            in_array($domain, PublicDomainConstants::PUBLIC_DOMAINS) &&
            request()->input('businessType', strtolower(request()->user()?->ownerBusinessType?->type ?: '')) == BusinessType::VENDOR->toLowercase()
        ) {
            $fail('Public email providers are not allowed. Please use a business email.');
        }

    }
}
