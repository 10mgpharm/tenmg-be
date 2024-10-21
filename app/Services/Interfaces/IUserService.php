<?php

namespace App\Services\Interfaces;

use App\Enums\BusinessType;
use App\Models\User;
use App\Models\Role;

interface IUserService
{
    /**
     * Store a new user and associated business based on the validated input.
     * This method starts a transaction to ensure both user creation and business mapping
     * are completed successfully, or rolled back in case of failure.
     *
     * @param array $validated The validated input data for creating the user and business.
     * @return User|null The created User model or null in case of failure.
     */
    public function store(array $validated): ?User;

    /**
     * Resolve the role for the user based on the provided business type.
     * Different business types correspond to different roles within the system.
     *
     * @param BusinessType $type The type of business for which the role is being resolved.
     * @return Role|null The role corresponding to the business type or null if no match.
     */
    public function resolveRole(BusinessType $type): ?Role;
}
