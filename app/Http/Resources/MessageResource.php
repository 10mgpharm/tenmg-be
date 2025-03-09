<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'conversationId' => $this->conversation_id,
            'id' => $this->id,
            'message' => $this->message,
            'readStatus' => $this->read_status,
            'readAt' => $this->read_at,
            'sentAt' => $this->created_at->diffForHumans(),
            'sender' => $this->sender->only(['id', 'name', 'avatar']),
            'receiver' => $this->receiver->only(['id', 'name', 'avatar']),

        ];
    }
}
