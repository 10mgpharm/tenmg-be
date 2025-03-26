<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceShoppingListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $productImage = new EcommerceProductResource($this->product);

        return [
            'id' => $this->id,
            'productName' => $this->product_name,
            'brandName' => $this->brand_name,
            'purchaseDate' => $this->purchase_date,
            'productId' => $this->product_id,
            'description' => $this->description,
            'image' => $productImage != null ?  $productImage?->thumbnailFile?->url ?? "N/A":$this->attachment ?? "N/A",
            'customer' => new UserResource($this->user),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
