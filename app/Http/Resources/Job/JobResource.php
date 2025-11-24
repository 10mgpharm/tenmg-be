<?php

namespace App\Http\Resources\Job;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'department' => $this->department,
            'employment_type' => $this->employment_type,
            'mission' => $this->mission,
            'responsibilities' => $this->responsibilities,
            'requirements' => $this->requirements,
            'compensation' => $this->compensation,
            'flexibility' => $this->flexibility,
            'how_to_apply' => $this->how_to_apply,
            'apply_url' => $this->apply_url,
            'location_type' => $this->location_type,
            'about_company' => $this->about_company,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
