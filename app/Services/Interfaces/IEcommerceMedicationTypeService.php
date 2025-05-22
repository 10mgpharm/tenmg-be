<?php

namespace App\Services\Interfaces;

use App\Models\EcommerceMedicationType;
use App\Models\User;

interface IEcommerceMedicationTypeService
{
    /**
     * Store a new medication type in the database.
     *
     * @param  array  $validated  The validated data for creating the medication type.
     * @param  \App\Models\User  $user  The user creating the medication type.
     * @return \App\Models\EcommerceMedicationType|null Returns the created medication type model or null on failure.
     */
    public function store(array $validated, User $user): ?EcommerceMedicationType;

    /**
     * Update an existing medication type in the database.
     *
     * @param  array  $validated  The validated data for updating the medication type.
     * @param  \App\Models\User  $user  The user updating the medication type.
     * @param  \App\Models\EcommerceMedicationType  $medication_type  The medication type to update.
     * @return bool|null Returns true if the medication type was updated, null on failure.
     */
    public function update(array $validated, User $user, EcommerceMedicationType $medication_type): ?bool;
}
