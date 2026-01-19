<?php

namespace App\Http\Resources\VirtualAccount;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VirtualAccountListResource extends JsonResource
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
            'walletId' => $this->wallet_id,
            'accountName' => $this->account_name,
            'bankName' => $this->bank_name,
            'accountNumber' => $this->account_number,
            'bankCode' => $this->bank_code,
            'status' => $this->status,
        ];
    }
}
