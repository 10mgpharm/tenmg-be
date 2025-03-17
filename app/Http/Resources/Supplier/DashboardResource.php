<?php

namespace App\Http\Resources\Supplier;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        return [
            'completeProfile' => (bool) (
                $user->ownerBusinessType &&
                $user->ownerBusinessType?->contact_person &&
                $user->ownerBusinessType?->contact_phone &&
                $user->ownerBusinessType?->contact_email
            ),
            'analytics' => $this->resource,
        ];
    }
}
