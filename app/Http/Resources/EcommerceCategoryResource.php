<?php

namespace App\Http\Resources;

use App\Enums\StatusEnum;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceCategoryResource extends JsonResource
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
            'slug' => $this->slug,
            'active' => $this->active,
            'status' => match (true) {
                in_array($this->status, StatusEnum::actives()) => StatusEnum::APPROVED->value,
                in_array($this->status, array_column(StatusEnum::cases(), 'value'), true) => $this->status,
                default => 'PENDING',
            },
            'created_at' => Carbon::parse($this->created_at)->format('M d, y h:i A'),
        ];
    }
}
