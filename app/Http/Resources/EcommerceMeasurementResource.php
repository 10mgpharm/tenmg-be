<?php

namespace App\Http\Resources;

use App\Enums\StatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceMeasurementResource extends JsonResource
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
            'active' => $this->active,
            'status' => match (true) {
                in_array($this->status, StatusEnum::actives()) => StatusEnum::APPROVED->value,
                in_array($this->status, array_column(StatusEnum::cases(), 'value'), true) => $this->status,
                default => 'PENDING',
            },
            'createdAt' => $this->created_at->format('M d, y h:i A'),
            // 'createdBy' => new UserResource($this->createdBy),
            // 'updatedBy' => new UserResource($this->updatedBy),
        ];
    }
}
