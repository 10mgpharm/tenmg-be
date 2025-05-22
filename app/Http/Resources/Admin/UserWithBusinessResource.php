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
        return array_merge(
            (new UserResource($this))->toArray($request),
            [
                'business' => $this->whenLoaded('businesses', fn() => $this->businesses->first() ? new BusinessResource($this->businesses->first()) : null),
            ]
        );
    }
}
