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
            'description' => $this->description,
            'slug' => $this->slug,
            'category' => new EcommerceCategoryResource($this->category),
            'brand' => new EcommerceBrandResource($this->brand),
            'medicationType' => new EcommerceMedicationTypeResource($this->medicationType),
            'presentation' => new EcommercePresentationResource($this->presentation),
            'measurement' => new EcommerceMeasurementResource($this->measurement),
            'package' => new EcommercePackageResource($this->package),
            'thumbnailFile' => $this->thumbnailFile?->url,
            'quantity' => $this->quantity,
            'actualPrice' => $this->actual_price,
            'discountPrice' => $this->discount_price,
            'lowStockLevel' => $this->low_stock_level,
            'outStockLevel' => $this->out_stock_level,
            'expiredAt' => $this->expired_at,
            'commission' => $this->commission,
            'productDetails' => $this->productDetails,
            'status' => in_array($this->status, ['ACTIVE', 'INACTIVE', 'SUSPENDED', 'ARCHIVED'], true)
            ? $this->status
            : 'PENDING',
            'inventory' => match (true) {
                $this->current_stock === null || $this->current_stock === 0 => 'OUT OF STOCK',
                $this->starting_stock === null || $this->current_stock <= $this->starting_stock / 2 => 'LOW STOCK',
                default => 'IN STOCK',
            },
            'comment' => $this->comment,
        ];
    }
}
