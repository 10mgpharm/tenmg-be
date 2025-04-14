<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditScoreResource extends JsonResource
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
            'identifier'=>$this->identifier,
            'txnEvaluationId'=>$this->txn_evaluation_id,
            'creditScoreResult'=> json_decode($this->credit_score_result),
            'affordability' => json_decode($this->affordability),
            'evaluation' => json_decode($this->creditEvaluation->evaluation_result),
            'customer' => new CreditCustomerResource($this->customer),
            'vendor' => $this->vendor,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
