<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditTransactionsResource extends JsonResource
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
            "business" => $this->business,
            "identifier" => $this->identifier,
            "amount" => $this->amount,
            "type" => $this->type,
            "transactionGroup" => $this->transaction_group,
            "description" => $this->description,
            "status" => $this->status,
            "paymentMethod" => $this->payment_method,
            "reference" => $this->reference,
            "walletId" => $this->wallet_id,
            "loanApplicationId" => $this->loanApplication_id,
            "meta" => $this->meta,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at
        ];
    }
}
