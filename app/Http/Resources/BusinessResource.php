<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'businessName' => $this->name,
            'contactPerson' => $this->contact_person,
            'contactPhone' => $this->contact_phone,
            'contactEmail' => $this->contact_email,
            'contactPersonPosition' => $this->contact_person_position,
            'businessAddress' => $this->address,
            'licenseNumber' => $this->license_number,
            'expiryDate' => $this->expiry_date?->diffForHumans(),
            'owner' => new UserResource($this->whenLoaded('owner')),
        ];
    }
}
