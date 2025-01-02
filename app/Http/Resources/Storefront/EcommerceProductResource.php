<?php

namespace App\Http\Resources\Storefront;

use App\Enums\StatusEnum;
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
            'description' => $this->description,
            'quantity' => $this->quantity,
            'actualPrice' => $this->actual_price,
            'discountPrice' => $this->discount_price,
            'minDeliveryDuration' => $this->min_delivery_duration,
            'maxDeliveryDuration' => $this->max_delivery_duration,
            'thumbnailUrl' => $this->thumbnailUrl,
            'expiredAt' => $this->expired_at,
            'productDetails' => $this->productDetails->only('essential', 'starting_stock', 'current_stock'),
            'status' => in_array($this->status, array_column(StatusEnum::cases(), 'value'), true)
            ? $this->status
            : 'DRAFTED',
            'inventory' => match (true) {
                $this->productDetails?->current_stock === null || $this->productDetails?->current_stock === 0 => 'OUT OF STOCK',
                $this->productDetails?->starting_stock === null || $this->productDetails?->current_stock <= $this->productDetails?->starting_stock / 2 => 'LOW STOCK',
                default => 'IN STOCK',
            },
            'comment' => $this->comment,
        ];
    }
}
