<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoadApplicationResource extends JsonResource
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
            'identifier' => $this->identifier,
            'businessId' => $this->business_id,
            'customer' => $this->customer,
            'requestedAmount' => $this->requested_amount,
            'interestAmount' => $this->interest_amount,
            'totalAmount' => $this->total_amount,
            'interestRate' => $this->interest_rate,
            'durationInMonths' => $this->duration_in_months,
            'source' => $this->source,
            'status' => $this->status,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at
        ];
    }
}
