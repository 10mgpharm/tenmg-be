<?php

namespace App\Http\Resources\Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionListResource extends JsonResource
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
            'transactionCategory' => $this->transaction_category?->value,
            'transactionType' => $this->transaction_type,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'balanceAfter' => (float) $this->balance_after,
            'transactionNarration' => $this->transaction_narration,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
