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
        $ownerBusinessType = $this->ownerBusinessType;

        $businessStatus = $this->businesses()
            ->firstWhere('user_id', $this->id)?->status ??
                'PENDING_VERIFICATION';

        if ($ownerBusinessType?->license_verification_status) {
            $businessStatus = match ($ownerBusinessType?->license_verification_status) {
                'PENDING' => 'PENDING_APPROVAL',
                'REJECTED' => 'REJECTED',
                default => now()->greaterThan($ownerBusinessType?->expiry_date)
                    ? 'LICENSE_EXPIRED'
                    : 'VERIFIED'
            };
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
            'owner' => (bool) ($ownerBusinessType?->type),

            'entityType' => $ownerBusinessType?->type ?? $this->businesses()
                ->firstWhere('user_id', $this->id)?->type,
            'businessName' => $ownerBusinessType?->name ?? $this->businesses()
                ->firstWhere('user_id', $this->id)?->name,
            'businessStatus' => $businessStatus,
            'completeProfile' => (bool) (
                $ownerBusinessType
                && $ownerBusinessType?->contact_person
                && $ownerBusinessType?->contact_phone
                && $ownerBusinessType?->contact_email
            ),
        ];
    }
}
