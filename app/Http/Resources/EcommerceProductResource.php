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
            'slug' => $this->slug,
            'category' => new EcommerceCategoryResource($this->category),
            'brand' => new EcommerceBrandResource($this->brand),
            'medicationType' => new EcommerceMedicationTypeResource($this->medicationType),
            'thumbnailFile' => $this->thumbnailFile?->url,
            'quantity' => $this->quantity,
            'actualPrice' => $this->actual_price,
            'discountPrice' => $this->discount_price,
            'minDeliveryDuration' => $this->min_delivery_duration,
            'maxDeliveryDuration' => $this->max_delivery_duration,
            'expiredAt' => $this->expired_at,
            'commission' => $this->commission,
            'productDetails' => $this->productDetails,
            'status' =>  in_array($this->status, ['ACTIVE', 'INACTIVE', 'SUSPENDED', 'ARCHIVED'], true)
            ? $this->status
            : 'DRAFTED',
            'inventory' => match (true) {
                $this->current_stock === null || $this->current_stock === 0 => 'OUT OF STOCK',
                $this->starting_stock === null || $this->current_stock <= $this->starting_stock / 2 => 'LOW STOCK',
                default => 'IN STOCK',
            },
        ];
    }
}
