<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'description' => $this->description,
            'isAdmin' => $this->is_admin,
            'isSupplier' => $this->is_supplier,
            'isPharmacy' => $this->is_pharmacy,
            'isVendor' => $this->is_vendor,
            'active' => $this->active,
            'isSubscribed' => $this->whenLoaded('subscribers')->isNotEmpty(),
        ];
    }
}
