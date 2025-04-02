<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\BusinessLimitedRecordResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoadApplicationForDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            'identifier' => $this->identifier,
            'customer' => $this->customer ? $this->customer->only(['name', 'email', 'id']) : null,
            'business' => new BusinessLimitedRecordResource($this->business),
            'requestedAmount' => $this->requested_amount,
            'interestAmount' => $this->interest_amount,
            'totalAmount' => $this->total_amount,
            'interestRate' => $this->interest_rate,
            'status' => $this->status,
        ];
    }
}
