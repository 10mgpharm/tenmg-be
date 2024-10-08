<?php

namespace App\Http\Resources\Supplier;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'completeProfile' => (bool) (
                $this->ownerBusinessType &&
                $this->ownerBusinessType?->contact_person &&
                $this->ownerBusinessType?->contact_phone &&
                $this->ownerBusinessType?->contact_email
            ),
        ];
    }
}
