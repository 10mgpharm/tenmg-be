<?php

namespace App\Http\Resources\Storefront;

use App\Enums\StatusEnum;
use App\Http\Resources\EcommerceBrandResource;
use App\Http\Resources\EcommerceCategoryResource;
use App\Http\Resources\EcommerceMeasurementResource;
use App\Http\Resources\EcommerceMedicationTypeResource;
use App\Http\Resources\EcommerceMedicationVariationResource;
use App\Http\Resources\EcommercePresentationResource;
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
            'active' => $this->active,
            'status' => match (true) {
                in_array($this->status, StatusEnum::actives()) => StatusEnum::ACTIVE->value,
                in_array($this->status, array_column(StatusEnum::cases(), 'value'), true) => $this->status,
                default => 'PENDING',
            },
            'inventory' => match (true) {
                $this->quantity === null || $this->quantity === 0 || $this->quantity == $this->out_stock_level => 'OUT OF STOCK',
                $this->quantity == $this->low_stock_level => 'LOW STOCK',
                default => 'IN STOCK',
            },
            'weight' => $this->weight,
            'thumbnailFile' => $this->thumbnailFile?->url,
            'quantity' => $this->quantity,
            'actualPrice' => $this->actual_price,
            'discountPrice' => $this->discount_price,
            'lowStockLevel' => $this->low_stock_level,
            'outStockLevel' => $this->out_stock_level,
            'expiredAt' => $this->expired_at,
            'commission' => $this->commission,
            'comment' => $this->comment,
            'company' => (request()->user() && request()->user()->hasRole('admin')) ? $this->business?->name ?? '10MG FAMILY PHARMACY' : null,
            'category' => new EcommerceCategoryResource($this->category),
            'brand' => new EcommerceBrandResource($this->brand),
            'variation' => new EcommerceMedicationVariationResource($this->variation),
            'presentation' => new EcommercePresentationResource($this->presentation),
            'measurement' => new EcommerceMeasurementResource($this->measurement),
            'medicationType' => new EcommerceMedicationTypeResource($this->medicationType),
            'productDetails' => $this->productDetails == null ? null : $this->productDetails->only('essential', 'starting_stock', 'current_stock', 'id', 'ecommerce_product_id'),
            'related_products' => $this->when(
                $this->related_products,
                EcommerceProductResource::collection(
                    EcommerceProduct::where('id', '!=', $this->id)
                        ->where(function ($query) {
                            $query->where('ecommerce_category_id', $this->ecommerce_category_id)
                                ->orWhere('ecommerce_medication_type_id', $this->ecommerce_medication_type_id);
                        })
                        ->limit(10)
                        ->latest('id')
                        ->get()
                ),
            )
        ];



        return $data;
    }
}
