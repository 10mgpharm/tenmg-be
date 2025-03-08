<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
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
            'sender' => $this->sender->only(['id', 'name', 'avatar']),
            'receiver' => $this->receiver->only(['id', 'name', 'avatar']),
            'latest' => new MessageResource($this->messages()->latest('id')->first()),
        ];
    }
}
