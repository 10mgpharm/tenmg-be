<?php

namespace App\Http\Resources\Admin;

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

        return [
            'revenue' => $this['revenue'],
            'revenuePerProduct' => $this['revenue_per_product'],
            'users' => $this['users'],
            'loans' => $this['loans'],
        ];
    }
}
