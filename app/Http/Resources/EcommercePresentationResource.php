<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommercePresentationResource extends JsonResource
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
            'status' => $this->status,
            'createdAt' => $this->created_at->format('M d, y h:i A')
            // 'createdBy' => new UserResource($this->createdBy),
            // 'updatedBy' => new UserResource($this->updatedBy),
        ];
    }
}
