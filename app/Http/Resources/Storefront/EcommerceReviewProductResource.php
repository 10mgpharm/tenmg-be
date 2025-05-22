<?php

namespace App\Http\Resources\Storefront;

use App\Http\Resources\EcommerceBrandResource;
use App\Http\Resources\EcommerceMeasurementResource;
use App\Http\Resources\EcommerceMedicationTypeResource;
use App\Http\Resources\EcommerceMedicationVariationResource;
use App\Http\Resources\EcommercePresentationResource;
use App\Models\EcommerceProductRating;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceReviewProductResource extends JsonResource
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
            'brand' => $this->whenLoaded('brand', fn ($brand) => new EcommerceBrandResource($brand)),
            'medicationType' => $this->whenLoaded('medicationType', fn ($medicationType) => new EcommerceMedicationTypeResource($medicationType)),
            'presentation' => $this->whenLoaded('presentation', fn ($presentation) => new EcommercePresentationResource($presentation)),
            'variation' => $this->whenLoaded('variation', fn ($variation) => new EcommerceMedicationVariationResource($variation)),
            'measurement' => $this->whenLoaded('measurement', fn ($measurement) => new EcommerceMeasurementResource($measurement)),
            'thumbnailFile' => $this->thumbnailFile?->url,
            'rating' =>  $this->whenLoaded('rating', fn ($rating) => new EcommerceProductRatingResource($rating)),
        ];
    }
}
