<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'name' => $this->name,
            'phone' => $this->phone,
            'status' => $this->active,
            'account_status' => $this->getRawOriginal('status'),
            'dateJoined' => $this->created_at,
            'businessName' => $this->ownerBusinessType?->name ?? $this->businesses()->firstWhere('user_id', $this->id)?->name,
        ];
    }
}
