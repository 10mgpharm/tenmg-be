<?php

namespace App\Http\Resources\ServiceProvider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceProviderResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'config' => $this->config,
            'metadata' => $this->metadata,
            'isBvnVerificationProvider' => $this->is_bvn_verification_provider,
            'isVirtualAccountProvider' => $this->is_virtual_account_provider,
            'isVirtualCardProvider' => $this->is_virtual_card_provider,
            'isPhysicalCardProvider' => $this->is_physical_card_provider,
            'isCheckoutProvider' => $this->is_checkout_provider,
            'isBankPayoutProvider' => $this->is_bank_payout_provider,
            'isMobileMoneyPayoutProvider' => $this->is_mobile_money_payout_provider,
            'isIdentityVerificationProvider' => $this->is_identity_verification_provider,
            'currenciesSupported' => $this->currencies_supported,
            'status' => $this->status,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
