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
            'contactPerson' => $this->contact_person,
            'contactPersonPosition' => $this->contact_person_position,
            'contactPhone' => $this->contact_phone,
            'address' => $this->address,
            'businessAddress' => $this->address,
            'licenseNumber' => $this->license_number,
            'expiryDate' => $this->expiry_date,
            'cacDocument' => $this->cac,
            'cacFileSize' => $this->cac_document->size,
            'type' => $this->type,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'verificationStatus' =>  match ($this->license_verification_status) {
                null => 'PENDING_VERIFICATION',
                'PENDING' => 'PENDING_APPROVAL',
                'REJECTED' => 'REJECTED',
                default => !$this->expiry_date || now()->greaterThan($this->expiry_date)
                ? 'EXPIRED'
                : 'VERIFIED'
            },
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
