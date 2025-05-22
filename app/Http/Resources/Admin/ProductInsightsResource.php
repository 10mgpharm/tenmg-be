<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductInsightsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $total_products_sold = $this['total_products_sold'];
        $total_revenue = $this['total_revenue'];

        return [
            'totalProductsSold' => [
                'midnightToSixAm' => $total_products_sold->midnight_to_six_am,
                'sixAmToTwelvePm' => $total_products_sold->six_am_to_twelve_pm,
                'twelvePmToSixPm' => $total_products_sold->twelve_pm_to_six_pm,
                'sixPmToMidnight' => $total_products_sold->six_pm_to_midnight,
            ],
            'totalRevenue' => [
                'midnightToSixAm' => $total_revenue->midnight_to_six_am,
                'sixAmToTwelvePm' => $total_revenue->six_am_to_twelve_pm,
                'twelvePmToSixPm' => $total_revenue->twelve_pm_to_six_pm,
                'sixPmToMidnight' => $total_revenue->six_pm_to_midnight,
            ],
            'bestSellingProducts' => BestSellingProductResource::collection($this['best_selling_products']),
        ];
    }
}
