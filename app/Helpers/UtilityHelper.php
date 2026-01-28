<?php

namespace App\Helpers;

use App\Common\ResponseMessages;
use App\Enums\BusinessType;
use App\Settings\LoanSettings;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

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
        $numbers = array_map(fn () => random_int(0, 9), range(1, 3));
        $letters = array_map(fn () => chr(random_int(65, 90)), range(1, 3));

        $shuffled = array_merge($numbers, $letters);
        shuffle($shuffled);

        return implode($shuffled);
    }

    /**
     * Generate business code
     */
    public static function generateBusinessCode(string $businessName): string
    {
        return trim(str_replace(' ', '', $businessName));
    }

    /**
     * Get lender, 10mg, and effective interest rates with caps applied.
     *
     * - Lender rate is clamped between 0% and 9%.
     * - 10mg rate is clamped at >= 0%.
     * - Combined (lender + 10mg) is hard-capped at 15%.
     */
    public static function getEffectiveInterestRates(?float $lenderRate = null, ?float $tenmgRate = null): array
    {
        $loanSettings = new LoanSettings;

        $configuredLenderRate = $lenderRate ?? (float) ($loanSettings->lenders_interest ?? 0);
        $configuredTenmgRate = $tenmgRate ?? (float) ($loanSettings->tenmg_interest ?? 0);

        // Clamp lender to [0, 9]
        $lenderRateClamped = max(0.0, min($configuredLenderRate, 9.0));

        // Tenmg share cannot be negative
        $tenmgRateClamped = max(0.0, $configuredTenmgRate);

        $combined = $lenderRateClamped + $tenmgRateClamped;

        // If combined exceeds 15, reduce 10mg portion so total is exactly 15
        if ($combined > 15.0) {
            $tenmgRateEffective = max(0.0, 15.0 - $lenderRateClamped);
        } else {
            $tenmgRateEffective = $tenmgRateClamped;
        }

        $effectiveRate = $lenderRateClamped + $tenmgRateEffective;

        return [
            'lender_rate' => $lenderRateClamped,
            'tenmg_rate' => $tenmgRateEffective,
            'effective_rate' => $effectiveRate,
        ];
    }

    public static function calculateInterestAmount(float $amount, int $durationInMonths): array
    {
        // Calculate interest amount using capped effective rate
        $loanSettings = new LoanSettings;
        $rates = self::getEffectiveInterestRates(
            $loanSettings->lenders_interest,
            $loanSettings->tenmg_interest
        );

        $interestRate = $rates['effective_rate'];

        $totalInterest = $amount * ($interestRate / 100);
        $monthlyInterestRate = ($interestRate / 100) * $durationInMonths;

        return [
            'interestRate' => $interestRate,
            'monthlyInterestRate' => $monthlyInterestRate,
            'interestAmount' => $totalInterest,
            'totalAmount' => $totalInterest + $amount,
        ];
    }

    // Generate the repayment breakdown using simple interest and capped rates
    public static function generateRepaymentBreakdown(float $principal, float $annualInterestRate, int $months, float $tenmgInterest): array
    {
        // Derive capped effective and 10mg rates from provided values
        $rates = self::getEffectiveInterestRates($annualInterestRate, $tenmgInterest);
        $effectiveRate = $rates['effective_rate'];
        $tenmgRate = $rates['tenmg_rate'];

        $totalInterest = $principal * ($effectiveRate / 100);
        $tenmgInterestTotal = $principal * ($tenmgRate / 100);
        $totalRepayment = $principal + $totalInterest;
        $monthlyPayment = $totalRepayment / $months;

        $repaymentBreakdown = [];
        $remainingBalance = $principal;

        for ($i = 1; $i <= $months; $i++) {
            $principalPortion = $totalRepayment / $months;
            $interestPortion = $totalInterest / $months;
            $tenmgInterestPortion = $tenmgInterestTotal / $months;
            $remainingBalance -= $principalPortion;
            $actualInterest = $interestPortion - $tenmgInterestPortion;

            $repaymentBreakdown[] = [
                'month' => Carbon::now()->addMonths($i)->format('F Y'),
                'totalPayment' => round($monthlyPayment, 2),
                'principal' => round($principalPortion, 2),
                'interest' => round($interestPortion, 2),
                'tenmgInterest' => round($tenmgInterestPortion, 2),
                'actualInterest' => round($actualInterest, 2),
                'balance' => round(max($remainingBalance, 0), 2),
            ];
        }

        return $repaymentBreakdown;
    }

    // Helper method to calculate EMI
    public static function calculateEmi(float $principal, float $monthlyInterestRate, int $months)
    {
        // EMI = [P * r * (1 + r)^n] / [(1 + r)^n - 1]
        $numerator = $principal * $monthlyInterestRate * pow(1 + $monthlyInterestRate, $months);
        $denominator = pow(1 + $monthlyInterestRate, $months) - 1;

        return $numerator / $denominator;
    }

    /**
     * Get the exception message based on the exception type.
     */
    public static function getExceptionMessage(Throwable $throwable): string
    {
        if ($throwable instanceof HttpException) {
            return Response::$statusTexts[$throwable->getStatusCode()] ?? 'HTTP Exception';
        }

        return $throwable->getMessage() ?: ResponseMessages::ERROR_PROCESSING_REQUEST;
    }

    /**
     * Get the status code based on the exception type.
     */
    public static function getStatusCode(Throwable $throwable): int
    {
        if ($throwable instanceof HttpException) {
            return $throwable->getStatusCode();
        }

        return 500;
    }

    /**
     * Get all allowed business types for registration in lowercase.
     *
     * @return array<string>
     */
    public static function getAllowedBusinessTypes(): array
    {
        return array_map(fn ($type) => $type->toLowerCase(), BusinessType::allowedForRegistration());
    }

    /**
     * generate slug reference
     *
     * @param  string  $prefix
     * @return void
     */
    public static function generateSlug($prefix = 'CL')
    {
        // Current date in the format YYYYMMDD
        $date = date('Ymd');

        // Current time in the format HHMMSS
        $time = date('His');

        // Generate a 4-character unique string
        // $uniqueString = strtoupper(substr(uniqid(), -4));

        // Generate a 4-character unique string with alphabets only
        $uniqueString = self::generateAlphaString(4);

        // Combine parts to form the slug
        $slug = sprintf('%s-%s-%s-%s', $prefix, $date, $time, $uniqueString);

        return $slug; // CL-20241103-162103-UXYV
    }

    /**
     * Generate a random alphabetic string of given length
     *
     * @param  int  $length
     * @return string
     */
    public static function generateAlphaString($length)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $alphaString = '';
        for ($i = 0; $i < $length; $i++) {
            $alphaString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $alphaString;
    }
}
