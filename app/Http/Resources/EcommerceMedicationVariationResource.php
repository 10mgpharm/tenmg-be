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
            'weight' => $this->weight,
            'description' => $this->description,
            'package_per_roll' => $this->package_per_roll,
            'presentation' => new EcommercePresentationResource($this->whenLoaded('presentation')),
            'medicationType' => new EcommerceMedicationTypeResource($this->whenLoaded('medicationType')),
            'status' => $this->status,
            'active' => $this->active,
            'createdAt' => $this->created_at->format('M d, y h:i A')
            // 'createdBy' => new UserResource($this->createdBy),
            // 'updatedBy' => new UserResource($this->updatedBy),
        ];
    }
}
