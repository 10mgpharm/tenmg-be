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
            'todaySales' => $this['today_sales'],
            'todayRevenue' => $this['today_revenue'],
            'todayOrder' => $this['today_order'],
            'revenuePerProduct' => $this['revenue_per_product'],
            'users' => $this['users'],
            'storeVisitors' => $this['store_visitors'],
            'onGoingLoans' => $this['onGoingLoans'],
            'loanRequests' => $this['loanRequests'],
            'loans' => $this['loans'],
        ];
    }
}
