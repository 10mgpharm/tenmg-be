<?php

namespace App\Http\Resources\Supplier;

use App\Enums\StatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceStoreAddressResource extends JsonResource
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
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'closestLandmark' => $this->closest_landmark,
            'street_address' => $this->street_address,
            'createdAt' => $this->created_at->format('M d, y h:i A'),
        ];
    }
}
