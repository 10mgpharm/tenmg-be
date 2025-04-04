<?php

namespace App\Http\Resources\Storefront;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceProductReviewResource extends JsonResource
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
            'name' => $this->name ?? $this->user?->name ?? 'Anonymous',
            'email' => $this->email ?? $this->user?->email ?? 'Anonymous',
            'comment' => $this->comment,
            'createdAt' => $this->created_at,
            'product' => $this->whenLoaded('product', fn ($product) => new EcommerceReviewProductResource($product)),
            'rating' => $this->whenLoaded('rating', fn ($rating) => new EcommerceProductRatingResource($rating)),
        ];
    }
}
