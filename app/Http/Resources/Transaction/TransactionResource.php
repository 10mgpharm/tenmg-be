<?php

namespace App\Http\Resources\Transaction;

use App\Http\Resources\Currency\CurrencyResource;
use App\Http\Resources\ServiceProvider\ServiceProviderResource;
use App\Http\Resources\Wallet\WalletResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'businessId' => $this->business_id,
            'walletId' => $this->wallet_id,
            'currencyId' => $this->currency_id,
            'transactionCategory' => $this->transaction_category?->value,
            'transactionType' => $this->transaction_type,
            'transactionMethod' => $this->transaction_method,
            'transactionReference' => $this->transaction_reference,
            'transactionNarration' => $this->transaction_narration,
            'transactionDescription' => $this->transaction_description,
            'amount' => (float) $this->amount,
            'processor' => $this->whenLoaded('processor', fn () => new ServiceProviderResource($this->processor)),
            'processorReference' => $this->processor_reference,
            'beneficiaryId' => $this->beneficiary_id,
            'status' => $this->status,
            'balanceBefore' => (float) $this->balance_before,
            'balanceAfter' => (float) $this->balance_after,
            'transactionData' => $this->transaction_data,
            'wallet' => $this->whenLoaded('wallet', fn () => new WalletResource($this->wallet)),
            'currency' => $this->whenLoaded('currency', fn () => new CurrencyResource($this->currency)),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
