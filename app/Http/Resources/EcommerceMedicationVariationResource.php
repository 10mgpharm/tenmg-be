<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceMedicationVariationResource extends JsonResource
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
            'strength_value' => $this->strength_value,
            'presentation' => new EcommercePresentationResource($this->whenLoaded('presentation')),
            'package' => new EcommercePackageResource($this->whenLoaded('package')),
            'medicationType' => new EcommerceMedicationTypeResource($this->whenLoaded('medicationType')),
            'status' => $this->status,
            'active' => $this->active,
            // 'createdBy' => new UserResource($this->createdBy),
            // 'updatedBy' => new UserResource($this->updatedBy),
        ];
    }
}
