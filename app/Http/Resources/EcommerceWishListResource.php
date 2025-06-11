<?php

namespace App\Http\Resources;

use App\Models\EcommerceProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceWishListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'productId' => $this->product_id,
            'product' => new EcommerceProductResource($this->product),
            'customer' => $this->customer,
            'updatedAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
