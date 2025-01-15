<?php

namespace App\Http\Resources\Storefront;

use App\Enums\StatusEnum;
use App\Http\Resources\EcommerceCategoryResource;
use App\Models\EcommerceProduct;
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
                $this->quantity === null || $this->quantity === 0 || $this->quantity == $this->out_stock_level => 'OUT OF STOCK',
                $this->quantity == $this->low_stock_level => 'LOW STOCK',
                default => 'IN STOCK',
            },
            'comment' => $this->comment,
            'category' => new EcommerceCategoryResource($this->category),
            'related_products' => $this->when(
                $this->related_products,
                EcommerceProductResource::collection(
                    EcommerceProduct::where('id', '!=', $this->id)
                        ->where(function ($query) {
                            $query->where('ecommerce_category_id', $this->ecommerce_category_id)
                                ->orWhere('ecommerce_brand_id', $this->ecommerce_brand_id)
                                ->orWhere('ecommerce_medication_type_id', $this->ecommerce_medication_type_id)
                                ->orWhere('ecommerce_presentation_id', $this->ecommerce_presentation_id);
                        })
                        ->limit(10)
                        ->latest()
                        ->get()
                ),
            )
        ];



        return $data;
    }
}
