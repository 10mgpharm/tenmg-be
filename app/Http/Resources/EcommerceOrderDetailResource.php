<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceOrderDetailResource extends JsonResource
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
            'actualPrice' => $this->actual_price,
            'discountPrice' => $this->discount_price,
            'quantity' => $this->quantity,
            'product' => $this->whenLoaded('product'),
            'createdAt' => $this->created_at->format('M d, y h:i A')
        ];
    }
}
