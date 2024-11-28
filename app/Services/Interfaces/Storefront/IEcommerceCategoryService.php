<?php

namespace App\Services\Interfaces\Storefront;

use App\Models\EcommerceCategory;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface IEcommerceCategoryService
{
    /**
     * Retrieve a paginated list of products associated with a specific category.
     *
     * This method accepts a request with optional filters such as product name, amount, date range, and brand.
     * It returns a paginated list of products that belong to the specified category, applying any filters
     * provided in the request.
     *
     * @param  Request  $request  The incoming HTTP request containing filter parameters.
     * @param  EcommerceCategory  $category  The category to filter products by.
     * @return LengthAwarePaginator A paginated list of products filtered by the provided parameters.
     */
    public function products(Request $request, EcommerceCategory $category): LengthAwarePaginator;

    /**
     * Retrieve a paginated list of products based on the provided filters, such as inventory status, category,
     * branch, medication type, variation, and package.
     *
     * The filters support both arrays and comma-separated values for multiple options.
     * Duplicates in array inputs are removed before processing.
     *
     * Available filters may include:
     * - product name or slug
     * - date range for product creation
     * - minimum and maximum product amounts
     * - product brand name
     *
     * @param  Request  $request  The incoming HTTP request containing filter parameters.
     * @return LengthAwarePaginator Paginated list of filtered products.
     */
    public function search(Request $request): LengthAwarePaginator;
}
