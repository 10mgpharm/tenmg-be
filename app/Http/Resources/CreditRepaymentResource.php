<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditRepaymentResource extends JsonResource
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
            "id" => $this->id,
            "loan" => new LoanResource($this->loan),
            "paymentId" => $this->payment_id,
            "principal" => $this->principal,
            "interest" => $this->interest,
            "balance" => $this->balance,
            "totalAmount" => $this->total_amount,
            "lateFee" => $this->late_fee,
            "dueDate" => $this->due_date,
            "paymentStatus" => $this->payment_status,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at
        ];
    }
}
