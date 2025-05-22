<?php

namespace App\Http\Resources\Supplier;

use App\Models\EcommerceBankAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceWalletResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'previousBalance' => $this->previous_balance ?? 0.0,
            'currentBalance' => $this->current_balance ?? 0.0,
            'bankAccount' => $this->whenLoaded('bankAccount', fn ($bank_account) => new EcommerceBankAccountResource($bank_account)),
        ];;
    }
}
