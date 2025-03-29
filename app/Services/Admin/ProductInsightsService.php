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
                'ONE_WEEK' => [$now->copy()->subWeek(), $now],
                'TWO_WEEKS' => [$now->copy()->subWeeks(2), $now],
                'ONE_MONTH' => [$now->copy()->subMonth(), $now],
                'THREE_MONTHS' => [$now->copy()->subMonths(3), $now],
                'SIX_MONTHS' => [$now->copy()->subMonths(6), $now],
                'ONE_YEAR' => [$now->copy()->subYear(), $now],
                default => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            };

            // Initialize the query for EcommerceOrderDetail
            $query = EcommerceOrderDetail::query()->whereHas('order', fn($query) => $query->where("status", '=', 'completed')->whereBetween('created_at', $date_range))->whereHas('product');

            // Fetch total quantity of products sold, grouped by time ranges
            $sales = $query
                ->selectRaw('
                    COALESCE(SUM(CASE WHEN HOUR(created_at) BETWEEN 0 AND 6 THEN quantity ELSE 0 END), 0) as midnight_to_six_am,
                    COALESCE(SUM(CASE WHEN HOUR(created_at) BETWEEN 6 AND 12 THEN quantity ELSE 0 END), 0) as six_am_to_twelve_pm,
                    COALESCE(SUM(CASE WHEN HOUR(created_at) BETWEEN 12 AND 18 THEN quantity ELSE 0 END), 0) as twelve_pm_to_six_pm,
                    COALESCE(SUM(CASE WHEN HOUR(created_at) BETWEEN 18 AND 24 THEN quantity ELSE 0 END), 0)as six_pm_to_midnight
                ')
                ->first();

            // Fetch total revenue, calculated from price * quantity, grouped by time ranges
            $revenues = $query
                ->selectRaw('
                COALESCE(SUM(CASE WHEN HOUR(created_at) BETWEEN 0 AND 6 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as midnight_to_six_am,
                COALESCE(SUM(CASE WHEN HOUR(created_at) BETWEEN 6 AND 12 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as six_am_to_twelve_pm,
                COALESCE(SUM(CASE WHEN HOUR(created_at) BETWEEN 12 AND 18 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as twelve_pm_to_six_pm,
                COALESCE(SUM(CASE WHEN HOUR(created_at) BETWEEN 18 AND 24 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as six_pm_to_midnight
            ')->first();
        

            // Fetch the top 3 best-selling products based on total quantity sold
            $best_selling_products = $query
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
