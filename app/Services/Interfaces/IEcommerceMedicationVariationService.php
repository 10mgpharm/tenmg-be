<?php

namespace App\Services\Interfaces;

use App\Models\EcommerceMedicationVariation;
use App\Models\User;

interface IEcommerceMedicationVariationService
{
    /**
     * Store a new ecommerce medication variation.
     *
     * This method validates the incoming data and creates a new
     * ecommerce medication variation associated with the given user. It uses a database
     * transaction to ensure data integrity, creating the medication variation with the
     * specified attributes and default values if not provided.
     *
     * @param array $validated The validated data for creating the medication variation.
     * @param User $user The user creating the medication variation.
     * @return EcommerceMedicationVariation|null The created medication variation or null on failure.
     * @throws Exception If the medication variation creation fails.
     */
    public function store(array $validated, User $user): ?EcommerceMedicationVariation;

    /**
     * Update an existing ecommerce medication variation.
     *
     * This method validates the incoming data and updates the specified
     * ecommerce medication variation. It uses a database transaction to ensure data integrity.
     * The medication variation will be updated with the new attributes provided in the
     * validated data, including the status and active state. If the name is
     * changed, the slug will be regenerated.
     *
     * @param array $validated The validated data for updating the medication variation.
     * @param User $user The user updating the medication variation.
     * @param EcommerceMedicationVariation $medication variation The medication variation to be updated.
     * @return bool|null True if the update was successful, null on failure.
     * @throws Exception If the medication variation update fails.
     */
    public function update(array $validated, User $user, EcommerceMedicationVariation $medication_variation): ?bool;

    /**
     * Delete an existing ecommerce medication variation.
     *
     * Prevents deletion if the medication variation has associated products.
     *
     * @param EcommerceMedicationVariation $medication variation The medication variation to be deleted.
     * @return bool Returns true if the medication variation was deleted, or false if it cannot be deleted due to associated products.
     */
    public function delete(EcommerceMedicationVariation $medication_variation): bool;

}
