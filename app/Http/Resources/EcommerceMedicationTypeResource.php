<?php

namespace App\Http\Resources;

use App\Models\EcommerceMedicationVariation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcommerceMedicationTypeResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'active' => $this->active,
            'status' => $this->status,
            // 'variations' => $this->variations,
            'variations' => $this->variations->filter(function (EcommerceMedicationVariation $variation) {
                return $variation->active;
            })
                ->map(function ($variation) {
                    return [
                        'id' => $variation->id,
                        'strength_value' => $variation->strength_value,
                        'package_per_roll' => $variation->package_per_roll,
                        'weight' => $variation->weight,
                        'medication_type' => $variation->medicationType?->name,
                        'presentation' => $variation->presentation?->name,
                        'measurement' => $variation->measurement?->name,
                        'active' => $variation->active,
                        'status' => $variation->status,
                        'statusComment' => $variation->status_comment,
                    ];
                })->toArray(),
            'created_at' => Carbon::parse($this->created_at)->format('M d, y h:i A'),
        ];
    }
}
