<?php

namespace App\Http\Resources\Supplier;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceTransactionResource extends JsonResource
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
            'walletId' => $this->ecommerce_wallet_id,
            'orderId' => $this->ecommerce_order_id,
            'txnType' => $this->txn_type,
            'txnGroup' => $this->txn_group,
            'amount' => $this->amount,
            'balanceBefore' => $this->balance_before,
            'balanceAfter' => $this->balance_after,
            'status' => $this->status,
        ];
    }
}
