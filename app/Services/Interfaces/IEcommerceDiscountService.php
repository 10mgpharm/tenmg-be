<?php

namespace App\Services\Interfaces;

use App\Models\EcommerceDiscount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface IEcommerceDiscountService
{
    /**
     * Retrieve a paginated list of discounts based on provided filters.
     *
     * Filters can include discount type, validity period, status, and more.
     *
     * @param Request $request The HTTP request containing filter parameters.
     * @return LengthAwarePaginator Paginated list of discounts.
     */
    public function index(Request $request): LengthAwarePaginator;

    /**
     * Create a new discount.
     *
     * Validates incoming data and creates a new discount associated with a business or global scope.
     *
     * @param array $validated Validated data for creating the discount.
     * @param User $user The user creating the discount.
     * @return EcommerceDiscount|null The created discount or null on failure.
     */
    public function store(array $validated, User $user): ?EcommerceDiscount;

    /**
     * Update an existing discount.
     *
     * Validates incoming data and updates the specified discount.
     *
     * @param array $validated Validated data for updating the discount.
     * @param User $user The user updating the discount.
     * @param EcommerceDiscount $discount The discount to be updated.
     * @return bool True if the update was successful, false otherwise.
     */
    public function update(array $validated, User $user, EcommerceDiscount $discount): bool;

    /**
     * Delete an existing discount.
     *
     * Prevents deletion if the discount is associated with active orders or products.
     *
     * @param EcommerceDiscount $discount The discount to be deleted.
     * @return bool True if the discount was deleted, false otherwise.
     */
    public function delete(EcommerceDiscount $discount): bool;

    /**
     * Search for discounts based on criteria such as name, type, or status.
     *
     * @param Request $request The HTTP request containing search parameters.
     * @return LengthAwarePaginator Paginated search results.
     */
    public function search(Request $request): LengthAwarePaginator;
}
