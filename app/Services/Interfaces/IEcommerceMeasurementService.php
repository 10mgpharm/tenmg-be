<?php

namespace App\Services\Interfaces;

use App\Models\EcommerceMeasurement;
use App\Models\User;

interface IEcommerceMeasurementService
{
    /**
     * Store a new ecommerce measurement.
     *
     * This method validates the incoming data and creates a new
     * ecommerce measurement associated with the given user. It uses a database
     * transaction to ensure data integrity, creating the measurement with the
     * specified attributes and default values if not provided.
     *
     * @param array $validated The validated data for creating the measurement.
     * @param User $user The user creating the measurement.
     * @return EcommerceMeasurement|null The created measurement or null on failure.
     * @throws Exception If the measurement creation fails.
     */
    public function store(array $validated, User $user): ?EcommerceMeasurement;

    /**
     * Update an existing ecommerce measurement.
     *
     * This method validates the incoming data and updates the specified
     * ecommerce measurement. It uses a database transaction to ensure data integrity.
     * The measurement will be updated with the new attributes provided in the
     * validated data, including the status and active state. If the name is
     * changed, the slug will be regenerated.
     *
     * @param array $validated The validated data for updating the measurement.
     * @param User $user The user updating the measurement.
     * @param EcommerceMeasurement $measurement The measurement to be updated.
     * @return bool|null True if the update was successful, null on failure.
     * @throws Exception If the measurement update fails.
     */
    public function update(array $validated, User $user, EcommerceMeasurement $measurement): ?bool;

    /**
     * Delete an existing ecommerce measurement.
     *
     * Prevents deletion if the measurement has associated products.
     *
     * @param EcommerceMeasurement $measurement The measurement to be deleted.
     * @return bool Returns true if the measurement was deleted, or false if it cannot be deleted due to associated products.
     */
    public function delete(EcommerceMeasurement $measurement): bool;

}
