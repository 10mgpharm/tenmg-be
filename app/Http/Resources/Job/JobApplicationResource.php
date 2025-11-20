<?php

namespace App\Http\Resources\Job;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobApplicationResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'expected_salary' => $this->expected_salary,
            'salary_type' => $this->salary_type,
            'notice_period' => $this->notice_period,
            'referral_source' => $this->referral_source,
            'created_at' => $this->created_at,
        ];
    }
}
