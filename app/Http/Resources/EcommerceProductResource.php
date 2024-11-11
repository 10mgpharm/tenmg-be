<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceProductResource extends JsonResource
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
            'category' => $this->category->name,
            'brand' => $this->brand->name,
            'medicationType' => $this->medicationType->name,
            'quantity' => $this->quantity,
            'actualPrice' => $this->actual_price,
            'discountPrice' => $this->discount_price,
            'minDeliveryDuration' => $this->min_delivery_duration,
            'maxDeliveryDuration' => $this->max_delivery_duration,
            'expiredAt' => $this->expired_at,
            'commission' => $this->commission,
            'productDetails' => $this->productDetails,
        ];
    }
}
