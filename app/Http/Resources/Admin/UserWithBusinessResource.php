<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\BusinessResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserWithBusinessResource extends JsonResource
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
            'role' => strtoupper($this->type ?? $this->getRoleNames()->first()),
            'phone' => $this->phone,
            'status' => $this->active,
            'account_status' => $this->getRawOriginal('status'),
            'dateJoined' => $this->created_at,
            'business' => $this->whenLoaded('businesses', fn() => $this->businesses->first() ? new BusinessResource($this->businesses->first()) : null),

        ];
    }
}
