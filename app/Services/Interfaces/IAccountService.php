<?php

namespace App\Services\Interfaces;

use App\Models\User;

interface IAccountService
{
    /**
     * Update the user's profile with the provided data.
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function updateProfile(User $user, array $data): User;
}
