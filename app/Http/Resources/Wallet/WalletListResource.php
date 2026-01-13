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
        return [
            'id' => $this->id,
            'walletType' => $this->wallet_type?->value,
            'balance' => (float) $this->balance,
            'walletName' => $this->wallet_name,
            'currencyCode' => $this->whenLoaded('currency', fn () => $this->currency->code),
            'hasVirtualAccount' => $this->whenLoaded('virtualAccount', fn () => $this->virtualAccount !== null),
        ];
    }
}
