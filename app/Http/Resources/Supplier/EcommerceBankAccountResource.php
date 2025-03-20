<?php

namespace App\Http\Resources\Supplier;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceBankAccountResource extends JsonResource
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
            'accountName' => $this->account_name,
            'accountNumber' => $this->account_number,
            'bankName' => $this->bank_name,
            'bankCode' => $this->bank_code,
            'active' => $this->active,
        ];
    }
}
