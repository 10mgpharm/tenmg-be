<?php

namespace App\Services\Credit;

use App\Models\Business;
use App\Models\LenderKycSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LenderKycTierService
{
    /**
     * Determines the next tier to initiate based on highest completed tier
     *
     * @return string|null Returns tier_1, tier_2, tier_3, or null if all tiers completed
     */
    public function getNextTier(Business $lenderBusiness): ?string
    {
        $highestCompleted = $this->getHighestCompletedTier($lenderBusiness);

        if (! $highestCompleted) {
            // No completed tiers, start with tier_1
            return 'tier_1';
        }

        return match ($highestCompleted) {
            'tier_1' => 'tier_2',
            'tier_2' => 'tier_3',
            'tier_3' => null, // All tiers completed
            default => 'tier_1', // Fallback
        };
    }

    /**
     * Gets the highest completed tier for a lender business
     *
     * @return string|null Returns tier_1, tier_2, tier_3, or null if none completed
     */
    public function getHighestCompletedTier(Business $lenderBusiness): ?string
    {
        // First check the cached value on the business model
        if ($lenderBusiness->highest_completed_kyc_tier) {
            return $lenderBusiness->highest_completed_kyc_tier;
        }

        // If not cached, check completed sessions
        $completedSessions = LenderKycSession::where('lender_business_id', $lenderBusiness->id)
            ->where('status', 'successful')
            ->whereNotNull('completed_tier')
            ->orderByRaw("FIELD(completed_tier, 'tier_1', 'tier_2', 'tier_3') DESC")
            ->first();

        if ($completedSessions) {
            return $completedSessions->completed_tier;
        }

        return null;
    }

    /**
     * Marks a tier as completed and updates the business record
     *
     * @param  string  $tier  The tier that was completed (tier_1, tier_2, tier_3)
     */
    public function markTierCompleted(Business $lenderBusiness, string $tier): void
    {
        try {
            DB::beginTransaction();

            $currentHighest = $this->getHighestCompletedTier($lenderBusiness);

            // Determine if this tier is higher than current highest
            $tierOrder = ['tier_1' => 1, 'tier_2' => 2, 'tier_3' => 3];
            $currentOrder = $currentHighest ? ($tierOrder[$currentHighest] ?? 0) : 0;
            $newOrder = $tierOrder[$tier] ?? 0;

            // Only update if this tier is higher than current highest
            if ($newOrder > $currentOrder) {
                $lenderBusiness->update([
                    'highest_completed_kyc_tier' => $tier,
                ]);

                Log::info('KYC tier marked as completed', [
                    'lender_business_id' => $lenderBusiness->id,
                    'tier' => $tier,
                    'previous_highest' => $currentHighest,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to mark KYC tier as completed', [
                'lender_business_id' => $lenderBusiness->id,
                'tier' => $tier,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validates if a tier can be initiated based on completion status
     *
     * @param  string  $tier  The tier to check (tier_1, tier_2, tier_3)
     */
    public function canInitiateTier(Business $lenderBusiness, string $tier): bool
    {
        $highestCompleted = $this->getHighestCompletedTier($lenderBusiness);

        // Can always initiate tier_1
        if ($tier === 'tier_1') {
            return true;
        }

        // For tier_2, must have completed tier_1
        if ($tier === 'tier_2') {
            return $highestCompleted === 'tier_1';
        }

        // For tier_3, must have completed tier_2
        if ($tier === 'tier_3') {
            return $highestCompleted === 'tier_2';
        }

        return false;
    }

    /**
     * Gets all completed tiers for a lender business
     *
     * @return array Array of completed tier strings
     */
    public function getCompletedTiers(Business $lenderBusiness): array
    {
        return LenderKycSession::where('lender_business_id', $lenderBusiness->id)
            ->where('status', 'successful')
            ->whereNotNull('completed_tier')
            ->distinct()
            ->pluck('completed_tier')
            ->toArray();
    }
}
