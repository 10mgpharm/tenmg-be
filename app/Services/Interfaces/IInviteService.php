<?php

namespace App\Services\Interfaces;

use App\Models\Invite;
use App\Models\User;

/**
 * Interface IInviteService
 *
 * Defines the contract for handling invite operations.
 */
interface IInviteService
{
    /**
     * Store a new invite in the database.
     *
     * @param array $data The validated data for creating the invite.
     * @param \App\Models\User $user The user creating the invite.
     * @return \App\Models\Invite|null Returns the created invite model or null on failure.
     */
    public function store(array $data, User $user): ?Invite;
}
