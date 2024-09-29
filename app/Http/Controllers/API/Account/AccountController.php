<?php

namespace App\Http\Controllers\API\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountProfileUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AttachmentService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(private AttachmentService $attachmentService) {}

    /**
     * Update the authenticated user's profile.
     *
     * @param AccountProfileUpdateRequest $request Validated account update request.
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(AccountProfileUpdateRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        // Filter non-empty fields from the validated data.
        $data = array_filter($validated);

        $created = null;

        // Save uploaded profile picture, if any.
        if ($request->hasFile('profilePicture')) {
            $created = $this->attachmentService->saveNewUpload(
                $request->file('profilePicture'),
                $user->id,
                User::class,
            );
        }

        // Update user profile with data and avatar_id (from uploaded file or current avatar).
        $user->update([...$data, 'avatar_id' => $created?->id ?: $user->avatar_id]);

    
        return $this->returnJsonResponse(
            message: 'Profile successfully updated.',
            data: (new UserResource($user->refresh()))
        );
    }
}
