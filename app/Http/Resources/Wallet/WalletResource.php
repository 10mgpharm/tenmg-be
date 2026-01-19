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
        // Handle wallet_type - it might be an enum or a string
        $walletType = $this->wallet_type;
        if ($walletType instanceof \BackedEnum) {
            $walletType = $walletType->value;
        } else {
            // If it's already a string or null, use it directly, otherwise get raw value
            $walletType = is_string($walletType) ? $walletType : ($this->getRawOriginal('wallet_type') ?? $walletType);
        }

        return [
            'id' => $this->id,
            'businessId' => $this->business_id,
            'walletType' => $walletType,
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
