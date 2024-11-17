<?php

namespace App\Services\Interfaces\Storefront;

use App\Http\Resources\Storefront\EcommerceProductResource;
use App\Models\EcommerceProduct;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface IEcommerceProductService
{
    /**
     * Retrieve the details of a specific product.
     *
     * This method returns the resource representation of a product, including its attributes
     * and any associated resources, based on the provided product instance.
     *
     * @param Request $request The incoming HTTP request.
     * @param EcommerceProduct $product The product to retrieve details for.
     * @return EcommerceProductResource The resource representing the product details.
     */
    public function show(Request $request, EcommerceProduct $product): EcommerceProductResource;

    /**
     * Retrieve a paginated list of products based on the provided filters, such as inventory status, category,
     * branch, medication type, variation, and package.
     *
     * The filters support both arrays and comma-separated values for multiple options.
     * Duplicates in array inputs are removed before processing.
     *
     * Available filters may include:
     * - product name or slug
     * - inventory status
     * - category or branch
     * - various product attributes like medication type, variation, and package
     * 
     * @param Request $request The incoming HTTP request containing filter parameters.
     * @return LengthAwarePaginator A paginated list of filtered products.
     */
    public function search(Request $request): LengthAwarePaginator;
}
