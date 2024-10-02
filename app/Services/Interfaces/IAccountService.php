<?php

namespace App\Services\Interfaces;

use App\Models\User;

interface IAccountService
{
    /**
     * Update the user's profile with the provided data.
     */
    public function updateProfile(User $user, array $data): User;
}
