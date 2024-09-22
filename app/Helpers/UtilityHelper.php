<?php

namespace App\Helpers;

use App\Common\ResponseMessages;
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

    public static function calculateInterestAmount(float $amount, int $durationInMonths): array
    {
        // calculate interest amount based on loan amount based on amount and duration
        $ANNUAL_INTEREST_RATE = config('app.interest_rate');
        $DAYS_IN_YEAR = 365; // 360
        $DAYS_IN_MONTH = 30;

        $MONTHLY_INTEREST_RATE = $ANNUAL_INTEREST_RATE / 100 / 12;

        $DAILY_INTEREST_PER_ANNUM = $ANNUAL_INTEREST_RATE / 100 / $DAYS_IN_YEAR;

        $DURATION_INTEREST = $DAILY_INTEREST_PER_ANNUM * ($DAYS_IN_MONTH * $durationInMonths);

        $DURATION_INTEREST = $DAILY_INTEREST_PER_ANNUM * ($DAYS_IN_MONTH * $durationInMonths);
        $INTEREST_AMOUNT = $DURATION_INTEREST * $amount;

        return [
            'interestRate' => $ANNUAL_INTEREST_RATE,
            'monthlyInterestRate' => $MONTHLY_INTEREST_RATE,
            'interestAmount' => $INTEREST_AMOUNT,
            'totalAmount' => $INTEREST_AMOUNT + $amount,
        ];
    }

    // Generate the repayment breakdown using the amortization formula
    public static function generateRepaymentBreakdown(float $principal, float $annualInterestRate, int $months)
    {
        // Convert annual interest rate to a monthly rate (decimal form)
        $monthlyInterestRate = $annualInterestRate / 100 / 12;

        // Calculate EMI (monthly payment)
        $emi = UtilityHelper::calculateEmi($principal, $monthlyInterestRate, $months);

        $repaymentBreakdown = [];
        $remainingPrincipal = $principal;

        // Generate repayment schedule
        for ($i = 1; $i <= $months; $i++) {
            // Calculate interest for the current month
            $interest = $remainingPrincipal * $monthlyInterestRate;

            // Calculate principal payment for the current month
            $principalPayment = $emi - $interest;

            // Reduce remaining principal
            $remainingPrincipal -= $principalPayment;

            // Add breakdown for this month
            $repaymentBreakdown[] = [
                'month' => Carbon::now()->addMonths($i)->format('F Y'),
                'totalPayment' => round($emi, 2),
                'principal' => round($principalPayment, 2),
                'interest' => round($interest, 2),
                'balance' => max(round($remainingPrincipal, 2), 0),
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
}
