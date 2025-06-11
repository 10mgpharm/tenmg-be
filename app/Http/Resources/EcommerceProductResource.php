<?php

namespace App\Http\Resources;

use App\Enums\StatusEnum;
use App\Http\Resources\Storefront\EcommerceProductRatingResource;
use App\Http\Resources\Storefront\EcommerceProductReviewOnlyResource;
use App\Http\Resources\Storefront\EcommerceProductReviewResource;
use App\Http\Resources\Storefront\EcommerceReviewProductResource;
use App\Models\EcommerceProduct;
use App\Models\EcommerceProductReview;
use Carbon\Carbon;
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
                in_array($this->status, StatusEnum::actives()) => StatusEnum::APPROVED->value,
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
            'createdAt' => Carbon::parse($this->created_at)->format('M d, y h:i A'),
            'updateAt' => Carbon::parse($this->updated_at)->format('M d, y h:i A'),
            'expiredAt' => $this->expired_at,
            'commission' => $this->commission,
            'comment' => $this->comment,
            'company' => (request()->user()?->hasRole('admin'))
                ? $this->business?->name ?? '10MG FAMILY PHARMACY'
                : null,
            'category' => $this->whenLoaded('category', fn() => new EcommerceCategoryResource($this->category->withoutRelations())),
            'brand' => $this->whenLoaded('brand', fn() => new EcommerceBrandResource($this->brand->withoutRelations())),
            'medicationType' => $this->whenLoaded('medicationType', fn() => new EcommerceMedicationTypeResource($this->medicationType->withoutRelations())),
            'presentation' => $this->whenLoaded('presentation', fn() => new EcommercePresentationResource($this->presentation->withoutRelations())),
            'variation' => $this->whenLoaded('variation', fn() => new EcommerceMedicationVariationResource($this->variation->withoutRelations())),
            'measurement' => $this->whenLoaded('measurement', fn() => new EcommerceMeasurementResource($this->measurement->withoutRelations())),
            'productDetails' => $this->productDetails?->only([
                'essential',
                'starting_stock',
                'current_stock',
                'id',
                'ecommerce_product_id',
            ]),
            'rating' => $this->whenLoaded('rating'),
            'reviews' => $this->whenLoaded('reviews', fn() => $this->reviews()
                ->paginate($request->has('perPage') ? $request->perPage : 20)
                ->withQueryString()
                ->through(fn(EcommerceProductReview $item) => EcommerceProductReviewOnlyResource::make($item))),
        ];
    }
}
