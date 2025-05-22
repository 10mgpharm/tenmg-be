<?php

namespace App\Services\Interfaces;

use App\Enums\BusinessType;
use App\Http\Requests\Auth\CompleteUserSignupRequest;
use App\Http\Requests\Auth\SignupUserRequest;
use App\Http\Requests\AuthProviderRequest;
use App\Models\Business;
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

    public function getId(): int;

    public function getBusiness(): ?Business;

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
    public function verifyUserEmail(User $user, string $code, string $type): ?User;

    /**
     * Return auth response
     */
    public function returnAuthResponse(User $user, PersonalAccessTokenResult $tokenResult, string $message = 'Sign in successful.', int $statusCode = Response::HTTP_OK): JsonResponse;

    /**
     * check email exist
     */
    public function emailExist(string $email): ?User;

    /**
     * create new user with business
     */
    public function googleSignUp(AuthProviderRequest $request): ?User;

    /**
     * Complete signup using google
     *
     * @return void
     */
    public function completeGoogleSignUp(CompleteUserSignupRequest $request);

    /**
     * Complete signup using credentials
     *
     * @return void
     */
    public function completeCredentialSignUp(CompleteUserSignupRequest $request);

    /**
     * handle account setup
     */
    public function handleAccountSetup(Business $adminBusiness, $businessType);
}
