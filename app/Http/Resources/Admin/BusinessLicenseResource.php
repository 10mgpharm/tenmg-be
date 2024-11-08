<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessLicenseResource extends JsonResource
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
            'businessName' => $this->name,
            'contactEmail' => $this->contact_email,
            'businessAddress' => $this->address,
            'licenseNumber' => $this->license_number,
            'expiryDate' => $this->expiry_date?->diffForHumans(),
            'cacDocument' => $this->cac,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'verificationStatus' =>  match ($this->license_verification_status) {
                null => 'PENDING_VERIFICATION',
                'PENDING' => 'PENDING_APPROVAL',
                'REJECTED' => 'REJECTED',
                default => !$this->expiry_date || now()->greaterThan($this->expiry_date)
                ? 'EXPIRED'
                : 'VERIFIED'
            },
        ];
    }
}
