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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'active' => (bool) $this->active == 1,
            'useTwoFactor' => (
                $this->use_two_factor && $this->two_factor_secret ? 'ACTIVE'
                : (!$this->use_two_factor && $this->two_factor_secret ? 'INACTIVE' : 'NOT_SETUP' )
            ),
            'avatar' => $this->avatar,
            'emailVerifiedAt' => $this->email_verified_at,
            'owner' => (bool) ($this->ownerBusinessType?->type),

            'entityType' => $this->ownerBusinessType?->type ?? $this->businesses()->firstWhere('user_id', $this->id)?->type,
            'businessName' => $this->ownerBusinessType?->name ?? $this->businesses()->firstWhere('user_id', $this->id)?->name,
            'businessStatus' => $this->ownerBusinessType?->status ?? $this->businesses()->firstWhere('user_id', $this->id)?->status,

            'completeProfile' => (bool) (
                $this->ownerBusinessType &&
                $this->ownerBusinessType?->contact_person &&
                $this->ownerBusinessType?->contact_phone &&
                $this->ownerBusinessType?->contact_email
            ),
        ];
    }
}
