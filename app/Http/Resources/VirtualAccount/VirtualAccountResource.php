<?php

namespace App\Http\Resources\VirtualAccount;

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
        return [
            'accountNumber' => $this->account_number,
            'bankName' => $this->bank_name,
            'accountName' => $this->account_name,
            'status' => $this->status,
        ];
    }
}
