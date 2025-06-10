<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DashboardRequest;
use App\Http\Resources\Admin\DashboardResource;
use App\Http\Resources\Admin\LoadApplicationForDashboardResource;
use App\Http\Resources\LoadApplicationResource;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderDetail;
use App\Models\EcommerceProduct;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\Role;
use App\Models\StoreVisitorCount;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
            ->whereDate('created_at', today());

            $order_query = EcommerceOrder::query()->whereDate('created_at', today());
            $loanRequests = LoanApplication::where('status', 'INITIATED')->orderBy("created_at", 'DESC')->take(5)->get();

            $analytics = [
                "today_sales" => $revenue_query->count(),
                "today_revenue" => $revenue_query->sum(DB::raw('quantity * COALESCE(discount_price, actual_price)')),
                "today_order" =>  $order_query->count(),
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
                'users' => Role::query()->get()->mapWithKeys(function ($role) {
                    $count = $role->users()
                        ->when($role->name === 'vendor', fn($q) => $q->whereHas('ownerBusinessType'))
                        ->count();
                    return [$role->name => $count];
                })->toArray(),
                'store_visitors' => StoreVisitorCount::query()->whereBetween('date', $date_range)->sum('count'),
                'onGoingLoans' => Loan::where('status', 'Ongoing')->count(),
                'loanRequests' => LoadApplicationResource::collection($loanRequests),
                'loans' => Loan::query()->with(['customer', 'business'])->where('status', 'Ongoing')->paginate($request->has('perPage') ? $request->perPage : 10)
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
}//
