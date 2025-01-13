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
            'customer' => $this->customer,
            'qtyTotal' => $this->qty_total,
            'orderTotal' => $this->order_total,
            'grandTotal' => $this->grand_total,
            'logisticTotal' => $this->logistic_total,
            'totalWeight' => $this->total_weight,
            'deliveryAddress' => $this->delivery_address,
            'deliveryType' => $this->delivery_type,
            'status' => $this->status,
            'items' => EcommerceCartItemResource::collection($this->orderDetails),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
