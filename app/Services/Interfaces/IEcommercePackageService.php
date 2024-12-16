<?php

namespace App\Services\Interfaces;

use App\Models\EcommercePackage;
use App\Models\User;

interface IEcommercePackageService
{
    /**
     * Store a new ecommerce package.
     *
     * This method validates the incoming data and creates a new
     * ecommerce package associated with the given user. It uses a database
     * transaction to ensure data integrity, creating the package with the
     * specified attributes and default values if not provided.
     *
     * @param array $validated The validated data for creating the package.
     * @param User $user The user creating the package.
     * @return EcommercePackage|null The created package or null on failure.
     * @throws Exception If the package creation fails.
     */
    public function store(array $validated, User $user): ?EcommercePackage;

    /**
     * Update an existing ecommerce package.
     *
     * This method validates the incoming data and updates the specified
     * ecommerce package. It uses a database transaction to ensure data integrity.
     * The package will be updated with the new attributes provided in the
     * validated data, including the status and active state. If the name is
     * changed, the slug will be regenerated.
     *
     * @param array $validated The validated data for updating the package.
     * @param User $user The user updating the package.
     * @param EcommercePackage $package The package to be updated.
     * @return bool|null True if the update was successful, null on failure.
     * @throws Exception If the package update fails.
     */
    public function update(array $validated, User $user, EcommercePackage $package): ?bool;

    /**
     * Delete an existing ecommerce package.
     *
     * Prevents deletion if the package has associated products.
     *
     * @param EcommercePackage $package The package to be deleted.
     * @return bool Returns true if the package was deleted, or false if it cannot be deleted due to associated products.
     */
    public function delete(EcommercePackage $package): bool;

}
