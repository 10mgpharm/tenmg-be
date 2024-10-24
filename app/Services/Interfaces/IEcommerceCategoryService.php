<?php

namespace App\Services\Interfaces;

use App\Models\EcommerceCategory;
use App\Models\User;

interface IEcommerceCategoryService
{
    /**
     * Store a new ecommerce category.
     *
     * This method validates the incoming data and creates a new
     * ecommerce category associated with the given user. It uses a database
     * transaction to ensure data integrity, creating the category with the
     * specified attributes and default values if not provided.
     *
     * @param array $validated The validated data for creating the category.
     * @param User $user The user creating the category.
     * @return EcommerceCategory|null The created category or null on failure.
     * @throws Exception If the category creation fails.
     */
    public function store(array $validated, User $user): ?EcommerceCategory;

    /**
     * Update an existing ecommerce category.
     *
     * This method validates the incoming data and updates the specified
     * ecommerce category. It uses a database transaction to ensure data integrity.
     * The category will be updated with the new attributes provided in the
     * validated data, including the status and active state. If the name is
     * changed, the slug will be regenerated.
     *
     * @param array $validated The validated data for updating the category.
     * @param User $user The user updating the category.
     * @param EcommerceCategory $category The category to be updated.
     * @return bool|null True if the update was successful, null on failure.
     * @throws Exception If the category update fails.
     */
    public function update(array $validated, User $user, EcommerceCategory $category): ?bool;
}
