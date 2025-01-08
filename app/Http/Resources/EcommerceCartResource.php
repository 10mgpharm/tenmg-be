<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceCartResource extends JsonResource
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
            'customerId' => $this->customer_id,
            'totalPrice' => $this->total_price,
            'status' => $this->status,
            'items' => EcommerceCartItemResource::collection($this->items),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
