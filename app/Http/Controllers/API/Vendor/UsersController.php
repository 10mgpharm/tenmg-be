<?php

namespace App\Http\Controllers\API\Vendor;

use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\DeleteUserRequest;
use App\Http\Requests\Vendor\ListUsersRequest;
use App\Http\Requests\Vendor\ShowUsersRequest;
use App\Http\Requests\Vendor\UpdateUserStatusRequest;
use App\Http\Resources\Admin\UserResource as AdminUserResource;
use App\Models\User;

class UsersController extends Controller
{

    /**
     * Retrieve all users.
     *
     * @param ListUserRequest $request Validated request instance.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ListUsersRequest $request)
    {

        $query = User::withinBusiness();

        if ($type = $request->input('type')) {
            $query->whereHas('roles', function ($query) use ($type) {
                $query->where('name', strtolower($type));
            });
        }

        if ($email = $request->input('email')) {
            $query->where('email', 'like', '%' . $email . '%');
        }

        if ($user = $request->input('user')) {
            $query->where(fn($q) => $q->orWhere('name', 'like', '%' . $user . '%')
                ->orWhere('email', 'like', '%' . $user . '%'));
        }

        if (!is_null($request->input('active'))) {
            $query->where('active', $request->input('active'));
        }

        if ($status = $request->input('status')) {
            $status = is_array($status) ? array_unique(array_map('trim', array_map('strtoupper', $status))) : array_unique(array_map('trim', array_map('strtoupper', explode(",", $status))));
            $query->whereIn('status', $status);
        }

        $users = $query->paginate($request->has('perPage') ? $request->perPage : 10)
            ->withQueryString()
            ->through(fn(User $item) => AdminUserResource::make($item));

        return $this->returnJsonResponse(
            message: 'Users successfully fetched.',
            data: $users
        );
    }


    /**
     * Show a user.
     */
    public function show(ShowUsersRequest $request, User $user)
    {
        return $user
            ? $this->returnJsonResponse(
                message: 'User successfully fetched.',
                data: new AdminUserResource($user)
            )
            : $this->returnJsonResponse(
                message: 'Oops, cannot fetch user at the moment. Please try again later.'
            );
    }

    /**
     * Update the status of a user.
     *
     * This method allows an admin to update the status of a user.
     * It validates the request, updates the user record, and returns a response.
     *
     * @param UpdateUserStatusRequest $request The validated request containing the new status.
     * @param User $user The user whose status is being updated.
     * @return JsonResponse The response indicating the result of the operation.
     */
    public function status(UpdateUserStatusRequest $request, User $user)
    {
        $validated = $request->validated();

        $isUpdated = $user->update([
            ...$validated,
            'active' => $validated['status'] === StatusEnum::ACTIVE->value,
        ]);

        if (!$isUpdated) {
            return $this->returnJsonResponse(
                message: 'Unable to update the user status at this time. Please try again later.'
            );
        }

        $status = strtolower($validated['status']);

        return $this->returnJsonResponse(
            message: "User status has been successfully updated to {$status}.",
            data: new AdminUserResource($user->refresh())
        );
    }

    public function destroy(DeleteUserRequest $request, User $user)
    {
        $user->forceDelete();

        return $this->returnJsonResponse(
            message: 'User successfully deleted.',
        );
    }
}
