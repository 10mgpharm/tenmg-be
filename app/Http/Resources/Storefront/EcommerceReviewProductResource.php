<?php

namespace App\Http\Resources\Storefront;

use App\Http\Resources\EcommerceBrandResource;
use App\Http\Resources\EcommerceMeasurementResource;
use App\Http\Resources\EcommerceMedicationTypeResource;
use App\Http\Resources\EcommerceMedicationVariationResource;
use App\Http\Resources\EcommercePresentationResource;
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
            'brand' => new EcommerceBrandResource($this->brand),
            'medicationType' => new EcommerceMedicationTypeResource($this->medicationType),
            'presentation' => new EcommercePresentationResource($this->presentation),
            'variation' => new EcommerceMedicationVariationResource($this->variation),
            'measurement' => new EcommerceMeasurementResource($this->measurement),
            'thumbnailFile' => $this->thumbnailFile?->url,
        ];
    }
}
