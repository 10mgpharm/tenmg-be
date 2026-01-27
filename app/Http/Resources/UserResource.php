<?php

namespace App\Http\Resources;

use App\Http\Resources\Wallet\WalletResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get business from loaded relationship or query
        $business = $this->relationLoaded('businesses')
            ? $this->businesses->first()
            : $this->businesses()->firstWhere('user_id', $this->id);

        $businessStatus = $business?->status ?? 'PENDING_VERIFICATION';

        if ($business?->type != 'ADMIN') {
            if ($business?->license_verification_status) {
                $businessStatus = match ($business?->license_verification_status) {
                    'PENDING' => 'PENDING_APPROVAL',
                    'REJECTED' => 'REJECTED',
                    default => now()->greaterThan($business?->expiry_date)
                        ? 'LICENSE_EXPIRED'
                        : 'VERIFIED'
                };
            }
        }

        $response = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'active' => (bool) $this->active == 1,
            'useTwoFactor' => $this->two_factor_secret ?
                ($this->use_two_factor ? 'ACTIVE' : 'INACTIVE') :
                'NOT_SETUP',
            'avatar' => $this->avatar,
            'emailVerifiedAt' => $this->email_verified_at,
            'owner' => (bool) ($this->ownerBusinessType),
            'entityType' => $business?->type,
            'role' => strtoupper($this->type ?? $this->getRoleNames()->first()),
            'businessName' => $business?->name,
            'businessStatus' => $businessStatus,
            'completeProfile' => (bool) (
                $business
                && $business?->contact_person
                && $business?->contact_phone
                && $business?->contact_email
            ),
        ];

        // Include wallets from business
        if ($business) {
            // Load wallets with currency and virtual account relationships
            $business->load([
                'wallets.currency',
                'wallets.virtualAccount',
            ]);
            $response['wallets'] = WalletResource::collection($business->wallets);
        } else {
            $response['wallets'] = [];
        }

        // Only include lenderType and kycStatus when business type is LENDER
        if ($business?->type === 'LENDER') {
            if ($business?->lender_type) {
                $response['lenderType'] = $business->lender_type;
            }

            // Determine KYC status for lenders with enhanced tier information
            $latestKycSession = \App\Models\LenderKycSession::where('lender_business_id', $business->id)
                ->latest()
                ->first();

            $highestCompletedTier = null;
            if ($business->highest_completed_kyc_tier) {
                $highestCompletedTier = $business->highest_completed_kyc_tier;
            } else {
                // Fallback: check completed sessions if not cached on business
                $completedSession = \App\Models\LenderKycSession::where('lender_business_id', $business->id)
                    ->where('status', 'successful')
                    ->whereNotNull('completed_tier')
                    ->orderByRaw("FIELD(completed_tier, 'tier_1', 'tier_2', 'tier_3') DESC")
                    ->first();
                $highestCompletedTier = $completedSession?->completed_tier;
            }

            // New KYC payload (tier + status + combined) for frontend
            $kycTierRaw = $latestKycSession?->kyc_level
                ?? $latestKycSession?->completed_tier
                ?? $highestCompletedTier
                ?? null;

            $kycStatusRaw = $latestKycSession?->status
                ?? ($highestCompletedTier ? 'successful' : null);

            // Normalize tier: strip "tier_" prefix and keep numeric/string part
            $kycTier = $kycTierRaw
                ? ltrim(strtolower(str_replace('tier_', '', $kycTierRaw)))
                : '';

            $kycStatus = $kycStatusRaw
                ? strtolower($kycStatusRaw)
                : '';

            $response['kyc'] = [
                'tier' => $kycTier,
                'status' => $kycStatus,
            ];
        }

        return $response;
    }
}
