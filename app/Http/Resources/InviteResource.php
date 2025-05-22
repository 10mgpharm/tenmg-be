<?php

namespace App\Http\Resources;

use App\Http\Resources\Admin\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InviteResource extends JsonResource
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
            'fullName' => $this->full_name,
            'email' => $this->email,
            'status' => $this->status,
            'role' => strtoupper($this->role->name),
            'createdAt' => $this->created_at->format('Y-m-d H:i:s'),
            'user' => new UserResource($this->user),
        ];
    }
}
