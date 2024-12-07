<?php

namespace App\Http\Resources;

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
        $business = $this->businesses()->firstWhere('user_id', $this->id);
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

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'active' => (bool) $this->active == 1,
            'useTwoFactor' => $this->two_factor_secret ?
                ($this->use_two_factor ? 'ACTIVE' : 'INACTIVE') :
                'NOT_SETUP',
            'avatar' => $this->avatar,
            'emailVerifiedAt' => $this->email_verified_at,
            'owner' => (bool) ($this->ownerBusinessType),
            'entityType' => $business?->type,
            'businessName' => $business?->name,
            'businessStatus' => $businessStatus,
            'completeProfile' => (bool) (
                $business
                && $business?->contact_person
                && $business?->contact_phone
                && $business?->contact_email
            ),
        ];
    }
}
