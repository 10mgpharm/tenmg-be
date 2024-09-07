<?php

namespace App\Services\Interfaces;

use App\Enums\BusinessType;
use App\Http\Requests\Auth\SignupUserRequest;
use App\Models\Role;
use App\Models\User;

interface IAuthService
{
    /**
     * Get user
     *
     * @throws Exception
     */
    public function getUser(): User;

    /**
     * create new user with business
     */
    public function signUp(SignupUserRequest $request): ?User;

    /**
     * return role based on signup type
     */
    public function resolveSignupRole(BusinessType $type): ?Role;
}
