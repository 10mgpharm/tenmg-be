<?php

namespace App\Http\Resources\VirtualAccount;

use App\Http\Resources\Currency\CurrencyResource;
use App\Http\Resources\ServiceProvider\ServiceProviderResource;
use App\Http\Resources\Wallet\WalletResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VirtualAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Handle type - it might be an enum or a string
        $type = $this->type;
        if ($type instanceof \BackedEnum) {
            $type = $type->value;
        } else {
            $type = is_string($type) ? $type : ($this->getRawOriginal('type') ?? $type);
        }

        return [
            'id' => $this->id,
            'businessId' => $this->business_id,
            'walletId' => $this->wallet_id,
            'currencyId' => $this->currency_id,
            'type' => $type,
            'provider' => $this->whenLoaded('serviceProvider', fn () => new ServiceProviderResource($this->serviceProvider)),
            'providerReference' => $this->provider_reference,
            'providerStatus' => $this->provider_status,
            'accountName' => $this->account_name,
            'bankName' => $this->bank_name,
            'accountNumber' => $this->account_number,
            'accountType' => $this->account_type,
            'bankCode' => $this->bank_code,
            'routingNumber' => $this->routing_number,
            'countryCode' => $this->country_code,
            'iban' => $this->iban,
            'checkNumber' => $this->check_number,
            'sortCode' => $this->sort_code,
            'bankSwiftCode' => $this->bank_swift_code,
            'addressableIn' => $this->addressable_in,
            'bankAddress' => $this->bank_address,
            'status' => $this->status,
            'wallet' => $this->whenLoaded('wallet', fn () => new WalletResource($this->wallet)),
            'currency' => $this->whenLoaded('currency', fn () => new CurrencyResource($this->currency)),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
