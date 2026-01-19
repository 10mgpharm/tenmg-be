<?php

namespace App\Http\Resources\Wallet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletListResource extends JsonResource
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
            'walletType' => $walletType,
            'balance' => (float) $this->balance,
            'walletName' => $this->wallet_name,
            'currencyCode' => $this->whenLoaded('currency', fn () => $this->currency->code),
            'hasVirtualAccount' => $this->whenLoaded('virtualAccount', fn () => $this->virtualAccount !== null),
        ];
    }
}
