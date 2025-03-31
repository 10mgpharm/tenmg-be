<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DashboardRequest;
use App\Http\Resources\Admin\DashboardResource;
use App\Http\Resources\Admin\LoadApplicationForDashboardResource;
use App\Models\EcommerceOrderDetail;
use App\Models\EcommerceProduct;
use App\Models\Loan;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Fetch the admin's dashboard data.
     *
     * This method handles the retrieval of the authenticated admin's dashboard details.
     * The dashboard is specific to users with the 'admin' role, and the response
     * contains all relevant information related to the admin's account.
     */
    public function __invoke(DashboardRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Determine the date range based on the filter value
            $date_range = match ($validated['date_filter']) {
                'ONE_WEEK' => [now()->copy()->subWeek(), now()],
                'TWO_WEEKS' => [now()->copy()->subWeeks(2), now()],
                'ONE_MONTH' => [now()->copy()->subMonth(), now()],
                'THREE_MONTHS' => [now()->copy()->subMonths(3), now()],
                'SIX_MONTHS' => [now()->copy()->subMonths(6), now()],
                'ONE_YEAR' => [now()->copy()->subYear(), now()],
                default => [now()->copy()->startOfDay(), now()->copy()->endOfDay()],
            };

            $product_query = EcommerceProduct::query();
            $revenue_query = EcommerceOrderDetail::query()->whereHas('product')
            ->whereHas('order', fn($query) => $query->where('status', 'completed'))
                ->whereBetween('created_at', $date_range);
            $user_query = User::query()->whereBetween('created_at', $date_range);

            $analytics = [
                'revenue' => match (strtolower($validated['date_filter'])) {
                    'today' => $revenue_query->clone()->selectRaw('
                        COALESCE(SUM(CASE WHEN HOUR(created_at) BETWEEN 0 AND 6 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as midnight_to_six_am,
                        COALESCE(SUM(CASE WHEN HOUR(created_at) BETWEEN 6 AND 12 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as six_am_to_twelve_pm,
                        COALESCE(SUM(CASE WHEN HOUR(created_at) BETWEEN 12 AND 18 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as twelve_pm_to_six_pm,
                        COALESCE(SUM(CASE WHEN HOUR(created_at) BETWEEN 18 AND 24 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as six_pm_to_midnight
                    ')->first(),

                    'one_week' => $revenue_query->clone()->selectRaw('
                        COALESCE(SUM(CASE WHEN DAYNAME(created_at) = "Monday" THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as Monday,
                        COALESCE(SUM(CASE WHEN DAYNAME(created_at) = "Tuesday" THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as Tuesday,
                        COALESCE(SUM(CASE WHEN DAYNAME(created_at) = "Wednesday" THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as Wednesday,
                        COALESCE(SUM(CASE WHEN DAYNAME(created_at) = "Thursday" THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as Thursday,
                        COALESCE(SUM(CASE WHEN DAYNAME(created_at) = "Friday" THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as Friday,
                        COALESCE(SUM(CASE WHEN DAYNAME(created_at) = "Saturday" THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as Saturday,
                        COALESCE(SUM(CASE WHEN DAYNAME(created_at) = "Sunday" THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as Sunday
                    ')->first(),

                    'one_month' => $revenue_query->clone()->selectRaw('
                        COALESCE(SUM(CASE WHEN WEEK(created_at) = WEEK(NOW()) THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as week_one,
                        COALESCE(SUM(CASE WHEN WEEK(created_at) = WEEK(NOW()) - 1 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as week_two,
                        COALESCE(SUM(CASE WHEN WEEK(created_at) = WEEK(NOW()) - 2 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as week_three,
                        COALESCE(SUM(CASE WHEN WEEK(created_at) = WEEK(NOW()) - 3 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as week_four
                    ')->first(),

                    'three_months' => $revenue_query->clone()->selectRaw('
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) 
                            THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as current_month,
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) 
                            AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH)) 
                            THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as last_month,
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 2 MONTH)) 
                            AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 2 MONTH)) 
                            THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as two_months_ago
                    ')->first(),

                    'six_months' => $revenue_query->clone()->selectRaw('
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) 
                            THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as current_month,
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) 
                            AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH)) 
                            THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as last_month,
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 2 MONTH)) 
                            AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 2 MONTH)) 
                            THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as two_months_ago,
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 3 MONTH)) 
                            AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 3 MONTH)) 
                            THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as three_months_ago,
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 4 MONTH)) 
                            AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 4 MONTH)) 
                            THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as four_months_ago,
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 5 MONTH)) 
                            AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 5 MONTH)) 
                            THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as five_months_ago
                    ')->first(),

                    'one_year' => $revenue_query->clone()->selectRaw('
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = 1 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as January,
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = 2 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as February,
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = 3 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as March,
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = 4 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as April,
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = 5 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as May,
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = 6 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as June,
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = 7 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as July,
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = 8 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as August,
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = 9 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as September,
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = 10 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as October,
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = 11 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as November,
                        COALESCE(SUM(CASE WHEN MONTH(created_at) = 12 THEN COALESCE(discount_price, actual_price) * quantity ELSE 0 END), 0) as December
                    ')->first(),

                    default => [],
                },
                'revenue_per_product' => $product_query->clone()
                    ->leftJoin('ecommerce_order_details', 'ecommerce_products.id', '=', 'ecommerce_order_details.ecommerce_product_id')
                    ->leftJoin('ecommerce_orders', 'ecommerce_order_details.ecommerce_order_id', '=', 'ecommerce_orders.id')
                    ->selectRaw('
                        ecommerce_products.id,
                        ecommerce_products.name,
                        ecommerce_products.slug,
                        SUM(CASE 
                            WHEN ecommerce_orders.status = "completed" 
                            THEN ecommerce_order_details.quantity * COALESCE(ecommerce_order_details.discount_price, ecommerce_order_details.actual_price) 
                            ELSE 0 
                        END) as revenue
                    ')
                    ->groupBy('ecommerce_products.id')
                ->get(),
                'users' => Role::query()->withCount('users')->pluck('users_count', 'name')->toArray(),
                'loans' => Loan::query()->with(['customer', 'business'])->paginate($request->has('perPage') ? $request->perPage : 10)
                ->withQueryString()
                ->through(fn (Loan $item) => LoadApplicationForDashboardResource::make($item))
            ];

            return $this->returnJsonResponse(
                message: 'Admin dashboard successfully fetched.',
                data: new DashboardResource($analytics),
            );
        } catch (Exception $e) {
            throw new Exception('Failed to fetch admin dashboard: ' . $e->getMessage());
        }
    }
}
