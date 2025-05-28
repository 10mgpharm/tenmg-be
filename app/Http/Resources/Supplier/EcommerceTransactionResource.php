<?php

namespace App\Http\Resources\Supplier;

use App\Models\EcommerceOrderDetail;
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
            'name' => $this->supplier?->name,
            'orderId' => $this->ecommerce_order_id,
            'order' => $this->order?->only('id', 'amount', 'identifier', 'status', 'created_at'),
            'tenmg_commission' => $this->ecommerce_order_detail_id ? EcommerceOrderDetail::find($this->ecommerce_order_detail_id)->tenmg_commission : null,
            'txnType' => $this->txn_type,
            'txnGroup' => $this->txn_group,
            'amount' => $this->amount,
            'balanceBefore' => $this->balance_before,
            'balanceAfter' => $this->balance_after,
            'status' => $this->status,
            'created_at' => $this->created_at->format('M d, y h:i A'),
        ];
    }
}
