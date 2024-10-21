<?php

namespace App\Services\Interfaces;

use App\Models\EcommerceBrand;
use App\Models\User;

interface IEcommerceBrandService
{
    /**
     * Store a new ecommerce brand in the database.
     *
     * This method stores a new ecommerce brand using the provided validated data.
     * It wraps the operation in a database transaction to ensure atomicity.
     * The slug is generated automatically from the name if not provided.
     *
     * @param array $validated Array of validated data, including fields like 'name', 'status', and 'active'.
     * @param User $user The authenticated user who is creating the brand. The user is used to set the business and creator.
     * @return EcommerceBrand|null Returns the created EcommerceBrand instance on success, or null on failure.
     * @throws \Exception Throws an exception if the transaction or creation process fails.
     */
    public function store(array $validated, User $user): ?EcommerceBrand;


    /**
     * Update an existing ecommerce brand in the database.
     *
     * This method updates an existing ecommerce brand with new validated data. It handles updates within a database transaction to ensure data consistency.
     * If the name is updated, a new slug will be generated. 
     * 
     * @param array $validated Array of validated data to update the brand, such as 'name', 'status', and 'active'.
     * @param User $user The authenticated user performing the update. The user's ID is recorded as the updater.
     * @param EcommerceBrand $brand The existing EcommerceBrand instance that needs to be updated.
     * @return bool|null Returns true if the brand is successfully updated, or null on failure.
     * @throws \Exception Throws an exception if the transaction or update process fails.
     */
    public function update(array $validated, User $user, EcommerceBrand $brand): ?bool;
}
