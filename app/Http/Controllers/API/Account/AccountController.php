<?php

namespace App\Http\Controllers\API\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountProfileUpdateRequest;
use App\Http\Resources\UserResource;
use App\Services\AccountService;

class AccountController extends Controller
{
    public function __construct(private AccountService $accountService) {}

    /**
     * Update the authenticated user's profile.
     *
     * @param  AccountProfileUpdateRequest  $request  Validated account update request.
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(AccountProfileUpdateRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        // Filter non-empty fields from the validated data.
        $data = array_filter($validated);
        $user = $this->accountService->updateProfile($user, $data);

        return $this->returnJsonResponse(
            message: 'Profile successfully updated.',
            data: (new UserResource($user))
        );
    }
}
