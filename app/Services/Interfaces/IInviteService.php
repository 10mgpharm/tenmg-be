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


    /**
     * Retrieve the invitation details for a guest user.
     *
     * This method fetches the invite using the invite token from the query string, then prepares
     * and returns an array of the invite details, including role name, full name, email, and signed URLs 
     * for accepting or rejecting the invitation.
     *
     * @return array The invitation details including role, full name, email, and signed URLs.
     */
    public function view();

    /**
     * Accept an invite and create a new user based on the invite details.
     *
     * @param array $validated The validated data, including password and other form fields.
     * @param Invite $invite The invite model containing the invite details.
     * @return User The newly created user.
     * @throws Exception If the invite acceptance process fails.
     */
    public function accept(array $validated, Invite $invite): User;
}
