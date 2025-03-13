<?php

namespace App\Services\Admin;

use App\Http\Resources\Admin\ProductInsightsResource;
use App\Models\EcommerceOrderDetail;
use App\Models\User;
use App\Services\Interfaces\IProductInsightsService;
use Exception;
use Illuminate\Support\Facades\DB;

class ProductInsightsService implements IProductInsightsService
{
    /**
     * Get product insights based on a date range filter.
     *
     * @param array $validated The validated input data, including the filter type.
     * @return ProductInsightsResource
     * @throws Exception If data retrieval fails.
     */
    public function insights(array $validated, User $user): ProductInsightsResource
    {
        try {
            $filter = $validated['filter'];

            // Get the current timestamp
            $now = now();

            // Determine the date range based on the filter value
            $date_range = match ($filter) {
                'one_week' => [$now->copy()->subWeek(), $now],
                'two_weeks' => [$now->copy()->subWeeks(2), $now],
                'one_month' => [$now->copy()->subMonth(), $now],
                'three_months' => [$now->copy()->subMonths(3), $now],
                'six_months' => [$now->copy()->subMonths(6), $now],
                'one_year' => [$now->copy()->subYear(), $now],
                default => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            };

            // Fetch total quantity of products sold, grouped by time ranges
            $sales = EcommerceOrderDetail::whereHas('order', fn($query) => $query->whereBetween('created_at', $date_range))
                ->selectRaw('
                    SUM(CASE WHEN HOUR(created_at) BETWEEN 0 AND 6 THEN quantity ELSE 0 END) as midnight_to_six_am,
                    SUM(CASE WHEN HOUR(created_at) BETWEEN 6 AND 12 THEN quantity ELSE 0 END) as six_am_to_twelve_pm,
                    SUM(CASE WHEN HOUR(created_at) BETWEEN 12 AND 18 THEN quantity ELSE 0 END) as twelve_pm_to_six_pm,
                    SUM(CASE WHEN HOUR(created_at) BETWEEN 18 AND 24 THEN quantity ELSE 0 END) as six_pm_to_midnight
                ')
                ->first();

            // Fetch total revenue, calculated from price * quantity, grouped by time ranges
            $revenues = EcommerceOrderDetail::whereHas('order', fn($query) => $query->whereBetween('created_at', $date_range))
                ->selectRaw('
                    SUM(CASE WHEN HOUR(created_at) BETWEEN 0 AND 6 THEN actual_price * quantity ELSE 0 END) as midnight_to_six_am,
                    SUM(CASE WHEN HOUR(created_at) BETWEEN 6 AND 12 THEN actual_price * quantity ELSE 0 END) as six_am_to_twelve_pm,
                    SUM(CASE WHEN HOUR(created_at) BETWEEN 12 AND 18 THEN actual_price * quantity ELSE 0 END) as twelve_pm_to_six_pm,
                    SUM(CASE WHEN HOUR(created_at) BETWEEN 18 AND 24 THEN actual_price * quantity ELSE 0 END) as six_pm_to_midnight
                ')
                ->first();

            // Fetch the top 3 best-selling products based on total quantity sold
            $best_selling_products = EcommerceOrderDetail::whereHas('order', fn($query) => $query->whereBetween('created_at', $date_range))
                ->join('ecommerce_products', 'ecommerce_order_details.ecommerce_product_id', '=', 'ecommerce_products.id')
                ->select('ecommerce_products.name', DB::raw('SUM(ecommerce_order_details.quantity) as total_sold'))
                ->groupBy('ecommerce_products.name')
                ->orderByDesc('total_sold')
                ->limit(3)
                ->get();

            // Return the insights as a structured resource
            return new ProductInsightsResource([
                'total_products_sold' => $sales,
                'total_revenue' => $revenues,
                'best_selling_products' => $best_selling_products,
            ]);
        } catch (Exception $e) {
            // Throw an exception with a meaningful error message
            throw new Exception('Failed to fetch product insights: ' . $e->getMessage());
        }
    }
}
