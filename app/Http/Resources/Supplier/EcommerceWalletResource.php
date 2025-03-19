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
        $bank_account = EcommerceBankAccount::whereNotNull('supplier_id')->where('supplier_id', $this->business_id ?? null)->latest()->first();

        return [
            'previousBalance' => $this->previous_balance ?? 0.0,
            'currentBalance' => $this->current_balance ?? 0.0,
            'bankAccount' => $bank_account ? new EcommerceBankAccountResource($bank_account) : null,
        ];;
    }
}
