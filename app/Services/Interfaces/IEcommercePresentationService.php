<?php

namespace App\Services\Interfaces;

use App\Models\EcommercePresentation;
use App\Models\User;

interface IEcommercePresentationService
{
    /**
     * Store a new ecommerce presentation.
     *
     * This method validates the incoming data and creates a new
     * ecommerce presentation associated with the given user. It uses a database
     * transaction to ensure data integrity, creating the presentation with the
     * specified attributes and default values if not provided.
     *
     * @param array $validated The validated data for creating the presentation.
     * @param User $user The user creating the presentation.
     * @return EcommercePresentation|null The created presentation or null on failure.
     * @throws Exception If the presentation creation fails.
     */
    public function store(array $validated, User $user): ?EcommercePresentation;

    /**
     * Update an existing ecommerce presentation.
     *
     * This method validates the incoming data and updates the specified
     * ecommerce presentation. It uses a database transaction to ensure data integrity.
     * The presentation will be updated with the new attributes provided in the
     * validated data, including the status and active state. If the name is
     * changed, the slug will be regenerated.
     *
     * @param array $validated The validated data for updating the presentation.
     * @param User $user The user updating the presentation.
     * @param EcommercePresentation $presentation The presentation to be updated.
     * @return bool|null True if the update was successful, null on failure.
     * @throws Exception If the presentation update fails.
     */
    public function update(array $validated, User $user, EcommercePresentation $presentation): ?bool;

    /**
     * Delete an existing ecommerce presentation.
     *
     * Prevents deletion if the presentation has associated products.
     *
     * @param EcommercePresentation $presentation The presentation to be deleted.
     * @return bool Returns true if the presentation was deleted, or false if it cannot be deleted due to associated products.
     */
    public function delete(EcommercePresentation $presentation): bool;

}
