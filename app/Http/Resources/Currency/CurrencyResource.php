<?php

namespace App\Http\Resources\Currency;

use App\Http\Resources\ServiceProvider\ServiceProviderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
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
            'classification' => $this->classification?->value,
            'name' => $this->name,
            'code' => $this->code,
            'symbol' => $this->symbol,
            'slug' => $this->slug,
            'decimalPlaces' => $this->decimal_places,
            'icon' => $this->icon,
            'description' => $this->description,
            'tier1Limits' => $this->tier_1_limits,
            'tier2Limits' => $this->tier_2_limits,
            'tier3Limits' => $this->tier_3_limits,
            'countryCode' => $this->country_code,
            'virtualAccountProvider' => $this->whenLoaded('virtualAccountProvider', fn () => new ServiceProviderResource($this->virtualAccountProvider)),
            'bankTransferPayoutProvider' => $this->whenLoaded('bankTransferPayoutProvider', fn () => new ServiceProviderResource($this->bankTransferPayoutProvider)),
            'status' => $this->status?->value,
            'isActive' => $this->is_active,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
