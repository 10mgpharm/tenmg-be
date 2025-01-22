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
        return [
            'id' => $this->id,
            'productName' => $this->product_name,
            'brandName' => $this->brand_name,
            'purchaseDate' => $this->purchase_date,
            'existIn10mgStore' => $this->exist_in_10mg_store,
            'image' =>$this->attachment?->url ?? "N/A",
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
