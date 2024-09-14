<?php

namespace App\Services\Interfaces;

use App\Enums\BusinessType;
use App\Http\Requests\Auth\SignupUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Laravel\Passport\PersonalAccessTokenResult;

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

    /**
     * verifyUserEmail
     */
    public function verifyUserEmail(User $user, string $otp): ?JsonResponse;

    /**
     * Return auth response
     */
    public function returnAuthResponse(User $user, PersonalAccessTokenResult $tokenResult, string $message = 'Sign in successful.', int $statusCode = Response::HTTP_OK): JsonResponse;

    /**
     * check email exist
     */
    public function emailExist(string $email): ?User;
}
