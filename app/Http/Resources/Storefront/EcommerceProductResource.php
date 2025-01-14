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
            'status' => match (true) {
                in_array($this->status, [StatusEnum::ACTIVE->value, StatusEnum::APPROVED->value]) => StatusEnum::ACTIVE->value,
                in_array($this->status, array_column(StatusEnum::cases(), 'value'), true) => $this->status,
                default => 'PENDING',
            },
            'inventory' => match (true) {
                $this->quantity === null || $this->quantity === 0 => 'OUT OF STOCK',
                $this->productDetails?->starting_stock !== null && 
                $this->quantity <= $this->productDetails?->starting_stock / 2 => 'LOW STOCK',
                default => 'IN STOCK',
            },
            'comment' => $this->comment,
        ];
    }
}
