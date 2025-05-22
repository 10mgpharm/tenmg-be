<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceCartItemResource extends JsonResource
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
            'product' => new EcommerceProductResource($this->product),
            'supplierId' => $this->supplier_id,
            'actualPrice' => $this->actual_price,
            'discountPrice' => $this->discount_price,
            'tenmgCommission' => $this->tenmg_commission,
            'quantity' => $this->quantity,
            'tenmgCommissionPercent' => $this->tenmg_commission_percent,
            'supplierAmount' => $this->discount_price - $this->tenmg_commission,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
