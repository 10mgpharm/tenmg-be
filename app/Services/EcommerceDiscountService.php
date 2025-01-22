<?php

namespace App\Services;

use App\Http\Resources\EcommerceDiscountResource;
use App\Models\EcommerceDiscount;
use App\Models\User;
use App\Services\Interfaces\IEcommerceDiscountService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EcommerceDiscountService implements IEcommerceDiscountService
{
    /**
     * Retrieve a paginated list of ecommerce discounts based on filters.
     *
     * @param Request $request The request containing filter criteria such as method, type, and search term.
     * @return LengthAwarePaginator Paginated list of discounts.
     */
    public function index(Request $request): LengthAwarePaginator
    {

        return EcommerceDiscount::businesses()
            ->when($request->input('search'), fn($query, $search) => $query->where('coupon_code', 'LIKE', "%{$search}%"))
            ->when(
                $request->input('applicationMethod'),
                fn($query, $method) => $query->whereIn(
                    'application_method',
                    is_array($method)
                        ? array_unique(array_map(fn($s) => strtoupper(trim($s)), $method))
                        : array_unique(array_map(fn($s) => strtoupper(trim($s)), explode(",", $method)))
                )
            )
            ->when(
                $request->input('discountType'),
                fn($query, $type) => $query->whereIn(
                    'type',
                    is_array($type)
                        ? array_unique(array_map(fn($s) => strtoupper(trim($s)), $type))
                        : array_unique(array_map(fn($s) => strtoupper(trim($s)), explode(",", $type)))
                )
            )
            ->when(
                $request->input('customerLimit'),
                fn($query, $customerLimit) => $query->whereIn(
                    'customer_limit',
                    is_array($customerLimit)
                        ? array_unique(array_map(fn($s) => strtoupper(trim($s)), $customerLimit))
                        : array_unique(array_map(fn($s) => strtoupper(trim($s)), explode(",", $customerLimit)))
                )
            )
            ->when(
                $request->input('startDate'),
                fn($query, $startDate) => $query->whereDate('start_date', '>=', $startDate)
            )
            ->when(
                $request->input('endDate'),
                fn($query, $endDate) => $query->whereDate('end_date', '<=', $endDate)
            )
            ->paginate($request->has('perPage') ? $request->perPage : 10)
            ->withQueryString()
            ->through(fn(EcommerceDiscount $item) => EcommerceDiscountResource::make($item));
    }

    /**
     * Create a new ecommerce discount.
     *
     * @param array $validated The validated data for creating the discount.
     * @param User $user The user creating the discount.
     * @return EcommerceDiscount|null The created discount or null on failure.
     * @throws Exception If the discount creation fails.
     */
    public function store(array $validated, User $user): ?EcommerceDiscount
    {
        try {
            return DB::transaction(function () use ($validated, $user) {

                $validated['created_by_id'] = $user->id;
                $validated['business_id'] = $user->ownerBusinessType?->id ?: $user->businesses()
                    ->firstWhere('user_id', $user->id)?->id;

                return EcommerceDiscount::create($validated);
            });
        } catch (Exception $e) {
            throw new Exception('Failed to create discount: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing ecommerce discount.
     *
     * @param array $validated The validated data for updating the discount.
     * @param User $user The user updating the discount.
     * @param EcommerceDiscount $discount The discount to update.
     * @return bool|null True on success, null on failure.
     * @throws Exception If the discount update fails.
     */
    public function update(array $validated, User $user, EcommerceDiscount $discount): bool
    {
        try {
            return DB::transaction(function () use ($validated, $user, $discount) {
                $validated['updated_by_id'] = $user->id;
                $validated = array_filter($validated);

                return $discount->update($validated);
            });
        } catch (Exception $e) {
            throw new Exception('Failed to update discount: ' . $e->getMessage());
        }
    }

    /**
     * Delete an existing ecommerce discount.
     *
     * @param EcommerceDiscount $discount The discount to delete.
     * @return bool True if the discount was deleted successfully, false otherwise.
     */
    public function delete(EcommerceDiscount $discount): bool
    {

        return $discount->delete();
    }

    /**
     * Search and filter eCommerce discounts.
     *
     * This method filters discounts based on the optional query parameters:
     * - `search`: Performs a case-insensitive search on the discount name.
     * - `method`: Filters by one or more methodes (comma-separated or as an array).
     * - `type`: Filters by one or more types (comma-separated or as an array).
     * 
     * The results are paginated and returned as transformed resources.
     *
     * @param Request $request The incoming HTTP request containing search and filter parameters.
     * @return \Illuminate\Pagination\LengthAwarePaginator Paginated collection of discounts as resources.
     */
    public function search(Request $request): LengthAwarePaginator
    {

        return self::index($request);
    }
}
