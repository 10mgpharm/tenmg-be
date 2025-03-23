<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditCustomerResource extends JsonResource
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
            'businessId' => $this->business_id,
            'avatarId' => $this->avatar_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'identifier' => $this->identifier,
            'lastEvaluationHistory' => new TxnHistoryResource($this->lastEvaluationHistory),
            'active' => $this->active,
            'reference' => $this->reference,
            'category' => $this->creditScore->category ?? "N/A",
            'score' => $this->creditScore->score_value ?? "N/A"
        ];
    }
}
