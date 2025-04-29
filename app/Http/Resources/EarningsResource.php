<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EarningsResource extends JsonResource
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
            'paidLoan' => $this->paidRepaymentSchedules->sum('interest'),
            'applicationId' => $this->application_id,
            'offerId' => $this->offer_id,
            'capitalAmount' => $this->capital_amount,
            'interestAmount' => $this->interest_amount,
            'totalAmount' => $this->total_amount,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
