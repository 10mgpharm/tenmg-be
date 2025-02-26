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

    return [
        'description' => $this->description,
        'event' => $this->event,
        'createAt' => $this->created_at ? $this->created_at->format('M d, y h:i A') : null,
        'properties' => $this->properties->except(['actor_business_id', 'target_business_id'])->toArray(),
        'actor' =>$this->properties->get('actor_business_id') ? User::find($this->properties->get('actor_business_id'))?->only(['name', 'email']) : null,
    ];
    }
}
