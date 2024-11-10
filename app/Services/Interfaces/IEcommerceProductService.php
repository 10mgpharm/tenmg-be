<?php

namespace App\Services\Interfaces;

use App\Models\EcommerceProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface IEcommerceProductService
{
    /**
     * Store a new product.
     *
     * This method creates a new product after ensuring the category, brand, and
     * medication type exist, creating them if necessary. It uses a transaction
     * to ensure all steps either complete or fail together.
     *
     * @param array $validated The validated data for creating the product.
     * @param User $user The user creating the product.
     * @return EcommerceProduct|null The created product or null on failure.
     * @throws Exception If product creation fails.
     */
    public function store(array $validated, User $user): ?EcommerceProduct;


    /**
     * Update an existing product.
     *
     * This method updates an existing product. It ensures that if the category,
     * brand, or medication type has been changed, they exist or are created.
     *
     * @param array $validated The validated data for updating the product.
     * @param User $user The user updating the product.
     * @param EcommerceProduct $product The product to be updated.
     * @return bool|null True on success, null on failure.
     * @throws Exception If the product update fails.
     */
    public function update(array $validated, User $user, EcommerceProduct $product): ?bool;

    /**
     * Retrieve a paginated list of products based on the provided filters, such as inventory status, category,
     * branch, medication type, variation, and package.
     *
     * The filters support both arrays and comma-separated values for multiple options.
     * Duplicates in array inputs are removed before processing.
     *
     * @return LengthAwarePaginator Paginated list of filtered products.
     */
    public function search(Request $request): LengthAwarePaginator;
}
