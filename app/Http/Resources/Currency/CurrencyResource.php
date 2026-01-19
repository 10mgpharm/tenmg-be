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
        // Handle classification - it might be an enum or a string
        $classification = $this->classification;
        if ($classification instanceof \BackedEnum) {
            $classification = $classification->value;
        } else {
            $classification = is_string($classification) ? $classification : ($this->getRawOriginal('classification') ?? $classification);
        }

        // Handle status - it might be an enum or a string
        $status = $this->status;
        if ($status instanceof \BackedEnum) {
            $status = $status->value;
        } else {
            $status = is_string($status) ? $status : ($this->getRawOriginal('status') ?? $status);
        }

        return [
            'id' => $this->id,
            'classification' => $classification,
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
            'virtualAccountProvider' => $this->when(
                $this->relationLoaded('virtualAccountProvider') && $this->virtualAccountProvider,
                fn () => new ServiceProviderResource($this->virtualAccountProvider)
            ),
            'tempVirtualAccountProvider' => $this->when(
                $this->relationLoaded('tempVirtualAccountProvider') && $this->tempVirtualAccountProvider,
                fn () => new ServiceProviderResource($this->tempVirtualAccountProvider)
            ),
            'virtualCardProvider' => $this->when(
                $this->relationLoaded('virtualCardProvider') && $this->virtualCardProvider,
                fn () => new ServiceProviderResource($this->virtualCardProvider)
            ),
            'bankTransferCollectionProvider' => $this->when(
                $this->relationLoaded('bankTransferCollectionProvider') && $this->bankTransferCollectionProvider,
                fn () => new ServiceProviderResource($this->bankTransferCollectionProvider)
            ),
            'mobileMoneyCollectionProvider' => $this->when(
                $this->relationLoaded('mobileMoneyCollectionProvider') && $this->mobileMoneyCollectionProvider,
                fn () => new ServiceProviderResource($this->mobileMoneyCollectionProvider)
            ),
            'bankTransferPayoutProvider' => $this->when(
                $this->relationLoaded('bankTransferPayoutProvider') && $this->bankTransferPayoutProvider,
                fn () => new ServiceProviderResource($this->bankTransferPayoutProvider)
            ),
            'mobileMoneyPayoutProvider' => $this->when(
                $this->relationLoaded('mobileMoneyPayoutProvider') && $this->mobileMoneyPayoutProvider,
                fn () => new ServiceProviderResource($this->mobileMoneyPayoutProvider)
            ),
            'status' => $status,
            'isActive' => $this->is_active,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
