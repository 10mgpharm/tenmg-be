<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TxnHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            'id'=>$this->id,
            'identifier'=>$this->identifier,
            'businessId'=>$this->business_id,
            'customer'=>$this->customerRecord,
            'transactionFileId'=>$this->transaction_file_id,
            'fileFormat'=>$this->file_format,
            'source'=>$this->source,
            'status'=>$this->status,
            'evaluationResult'=>json_decode($this->evaluation_result),
            'createdById'=>$this->created_by_id,
            'createdAt'=>$this->updated_at,
            'updatedAt'=>$this->updated_at,
        ];
    }
}
