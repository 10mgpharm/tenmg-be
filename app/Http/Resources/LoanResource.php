<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
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
            'identifier' => $this->identifier,
            'vendor' => $this->business,
            'customer' => $this->customer,
            'application' => $this->application,
            'offerId' => $this->offer_id,
            'capitalAmount' => $this->capital_amount,
            'interestAmount' => $this->interest_amount,
            'totalAmount' => $this->total_amount,
            'repaymemtStartDate' => $this->repayment_start_date,
            'repaymemtEndDate' => $this->repayment_end_date,
            'repaymentSchedule' => $this->repaymentSchedule,
            'status' => $this->status,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at
        ];
    }
}
