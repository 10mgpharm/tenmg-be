<?php

namespace App\Services\Credit;

use App\Enums\BusinessType;
use App\Helpers\UtilityHelper;
use App\Models\Business;
use App\Models\LenderMatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoanPreferenceService
{
    /**
     * Simulate a repayment plan using loan preferences (internally) but only return repayment info.
     *
     * @param  array  $payload  Additional request data (borrower_reference, transaction_history, etc.)
     */
    public function simulate(int $amount, ?int $tenorInMonths = null, array $payload = []): array
    {
        // System default allowed tenors (months)
        $allowedTenors = [1, 2, 3, 4];

        // Default tenor = 1 month if none is provided or invalid
        $tenorInMonths = $tenorInMonths && in_array($tenorInMonths, $allowedTenors, true)
            ? $tenorInMonths
            : 1;

        /**
         * Lender eligibility rules
         *
         * 10mg backend:
         * - Searches for available lenders who:
         *   - Support the selected tenor
         *   - Are active and open to new loans
         * - Among eligible lenders, recommend the one with the lowest rate (best offer)
         *
         * NOTE:
         * - Lenders only set a single interest rate (no per-tenor rate)
         * - The system controls allowed tenors (1, 2, 3, 4 months)
         * - Lender rate MUST NOT be more than 9% (we will cap it if configured higher)
         * - For now, we use a simple global min/max amount range (can be enhanced with instruction_config)
         */
        $globalMinAmount = 5000;  // Minimum loan amount
        $globalMaxAmount = null;  // Maximum loan amount (unlimited for now)

        // Fetch lenders with their settings
        $lenders = Business::where('type', BusinessType::LENDER->value)
            ->whereHas('lenderSetting')
            ->with('lenderSetting')
            ->get();

        // Build eligible lenders array
        $eligible = [];
        foreach ($lenders as $lender) {
            $setting = $lender->lenderSetting;
            if (! $setting) {
                continue; // Skip lenders without settings
            }

            $config = $setting->instruction_config ?? [];

            // Check if lender is active / open to new loans (defaults to true)
            $isActive = $config['active'] ?? true;
            if (! $isActive) {
                continue;
            }

            // Check if lender supports the selected tenor
            // If no specific supported_tenors are configured, fall back to system defaults
            $supportedTenors = $config['supported_tenors'] ?? $allowedTenors;
            if (! in_array($tenorInMonths, $supportedTenors, true)) {
                continue;
            }

            // Check if amount is within acceptable range
            // For now using global min/max, but can be overridden per-lender via instruction_config
            $minAmount = $config['min_amount'] ?? $globalMinAmount;
            $maxAmount = $config['max_amount'] ?? $globalMaxAmount;

            if ($amount < $minAmount) {
                continue;
            }

            if ($maxAmount !== null && $amount > $maxAmount) {
                continue;
            }

            $eligible[] = [
                'id' => $lender->id,
                'name' => $lender->name,
                'rate' => (float) $setting->rate,
                'instruction' => $setting->instruction,
                'instruction_config' => $config,
            ];
        }

        if (empty($eligible)) {
            return [
                'requested' => [
                    'amount' => $amount,
                    'tenor_in_months' => $tenorInMonths,
                ],
                'calculation' => null,
                'message' => 'No repayment plan matches this amount and tenor.',
            ];
        }

        // Pick the eligible lender with the lowest rate (best offer)
        usort($eligible, function (array $a, array $b) {
            return ($a['rate'] ?? 0) <=> ($b['rate'] ?? 0);
        });

        $picked = $eligible[0];

        // Get lender's base rate and derive capped effective rate (lender + 10mg, max 15%)
        $baseRate = $picked['rate'] ?? 5.0;
        $rates = UtilityHelper::getEffectiveInterestRates($baseRate, null);
        $lenderRate = $rates['lender_rate'];
        $tenmgRate = $rates['tenmg_rate'];
        $tenorRate = $rates['effective_rate'];

        // $lenderName = $picked['name'] ?? 'Lender';
        // $lenderInstruction = $picked['instruction'] ?? null;

        // Persist lender match record if borrower_reference is provided.
        // If the same borrower_reference is used again (e.g. amount/tenor/lender changes),
        // we update the existing record instead of creating a new one.
        if (! empty($payload['borrower_reference'])) {
            try {
                $vendorBusiness = null;

                // 1) If called with an authenticated user (dashboard, etc.), derive vendor from the user
                $user = Auth::user();
                if ($user) {
                    $vendorBusiness = $user->ownerBusinessType ?? $user->businesses()->first();
                }

                // 2) If called via clientAuth (Public-Key/Secret-Key), the middleware attaches
                //    the vendor Business model into the payload as 'business'
                if (! $vendorBusiness && isset($payload['business']) && $payload['business'] instanceof Business) {
                    $vendorBusiness = $payload['business'];
                }

                if ($vendorBusiness) {
                    LenderMatch::updateOrCreate(
                        ['borrower_reference' => $payload['borrower_reference']],
                        [
                            'vendor_business_id' => $vendorBusiness->id,
                            'lender_business_id' => $picked['id'],
                            'amount' => $amount,
                            'currency' => $payload['currency'] ?? 'NGN',
                            'default_tenor' => $tenorInMonths,
                            'businessname' => $payload['businessname'] ?? null,
                            'transaction_history' => $payload['transaction_history'] ?? null,
                            'product_items' => $payload['product_items'] ?? null,
                            'callback_url' => $payload['callback_url'] ?? null,
                            'status' => 'matched',
                        ]
                    );
                } else {
                    Log::warning('Could not determine vendor business for lender match', [
                        'source' => $payload['type'] ?? null,
                        'borrower_reference' => $payload['borrower_reference'],
                    ]);
                }
            } catch (\Exception $e) {
                // Log error but don't fail the simulation
                Log::error('Failed to create or update lender match record', [
                    'error' => $e->getMessage(),
                    'borrower_reference' => $payload['borrower_reference'] ?? null,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Simple interest calculation based on effective tenor rate
        $interestAmount = $amount * ($tenorRate / 100);
        $totalRepayable = $amount + $interestAmount;
        $monthlyRepayment = $totalRepayable / $tenorInMonths;

        // Build simple monthly repayment schedule (equal payments)
        $schedule = [];
        $today = Carbon::today();
        $remaining = $totalRepayable;

        for ($i = 1; $i <= $tenorInMonths; $i++) {
            // For the last installment, adjust for rounding so total matches exactly
            if ($i === $tenorInMonths) {
                $amountThisInstallment = round($remaining, 2);
            } else {
                $amountThisInstallment = round($monthlyRepayment, 2);
                $remaining -= $amountThisInstallment;
            }

            $schedule[] = [
                'installment_number' => $i,
                'due_date' => $today->copy()->addMonths($i)->toDateString(),
                'amount' => $amountThisInstallment,
            ];
        }

        return [
            'requested' => [
                'amount' => $amount,
                'tenor_in_months' => $tenorInMonths,
                'tenor_interest_rate' => $tenorRate,
            ],
            // 'lender' => [
            //     'name' => $lenderName,
            //     'instruction' => $lenderInstruction,
            // ],
            'calculation' => [
                'principal' => (int) $amount,
                'tenor_interest_rate' => $tenorRate,
                'tenor_in_months' => $tenorInMonths,
                'interest_amount' => round($interestAmount, 2),
                'total_repayable' => round($totalRepayable, 2),
                'monthly_repayment' => round($monthlyRepayment, 2),
                'repayment_schedule' => $schedule,
                'summary_text' => sprintf(
                    'For a purchase of ₦%s spread over %d months at %.2f%%, your total repayment will be ₦%s and your estimated monthly payment will be ₦%s.',
                    number_format($amount),
                    $tenorInMonths,
                    $tenorRate,
                    number_format(round($totalRepayable, 2)),
                    number_format(round($monthlyRepayment, 2))
                ),
            ],
        ];
    }
}
