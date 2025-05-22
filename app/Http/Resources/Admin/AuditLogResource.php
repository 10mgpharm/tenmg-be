<?php

namespace App\Http\Resources\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actorId = $this->causer_id;
        $actor = $actorId ? User::find($actorId) : null;

        if ($actor) {
            $actor = array_merge(
                $actor->only(['name', 'email', 'avatar']),
                ['role' => strtoupper($actor->getRoleNames()->first())] // Use $actor, not $user
            );
        }
        
        return [
            'id' => $this->id,
            'description' => $this->description,
            'event' => $this->event,
            'createdAt' => $this->created_at ? $this->created_at->format('M d, y h:i A') : null,
            'properties' => $this->properties->except(['actor_business_id', 'target_business_id'])->toArray(),
            'actor' => $actor,
        ];
    }
}
