<?php

namespace App\Http\Resources\Wallet;

use App\Http\Resources\Currency\CurrencyResource;
use App\Http\Resources\VirtualAccount\VirtualAccountResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
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
            'walletType' => $this->wallet_type?->value,
            'currencyId' => $this->currency_id,
            'balance' => (float) $this->balance,
            'walletName' => $this->wallet_name,
            'currency' => $this->whenLoaded('currency', fn () => new CurrencyResource($this->currency)),
            'virtualAccount' => $this->whenLoaded('virtualAccount', fn () => new VirtualAccountResource($this->virtualAccount)),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
