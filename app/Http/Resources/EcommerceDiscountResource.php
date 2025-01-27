<?php

namespace App\Http\Resources;

use App\Http\Resources\Supplier\EcommerceProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceDiscountResource extends JsonResource
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
            'applicationMethod' => $this->application_method,
            'couponCode' => $this->coupon_code,
            'type' => $this->type,
            'amount' => $this->amount,
            'customerLimit' => $this->customer_limit,
            'startDate' => $this->start_date?->format('M d, y h:i A'),
            'endDate' => $this->end_date?->format('M d, y h:i A'),
            'applicableProducts' =>  EcommerceProductResource::collection($this->whenLoaded('applicableProducts')),
        ];
    }
}
