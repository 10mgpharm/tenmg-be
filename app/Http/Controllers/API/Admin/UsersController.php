<?php

namespace App\Http\Controllers\API\Admin;

use App\Enums\InAppNotificationType;
use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\DeleteUserRequest;
use App\Http\Requests\Admin\ListUserRequest;
use App\Http\Requests\Admin\ShowUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Resources\Admin\UserResource;
use App\Http\Resources\Admin\UserWithBusinessResource;
use App\Models\User;
use App\Notifications\Loan\LoanSubmissionNotification;
use App\Notifications\UserStatusNotification;
use App\Services\Admin\UserService;
use App\Services\AuditLogService;
use App\Services\InAppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;

class UsersController extends Controller
{
    public function __construct(private UserService $userService,) {}


    /**
     * Retrieve all users.
     *
     * @param ListUserRequest $request Validated request instance.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ListUserRequest $request)
    {

        $query = User::query()->with('businesses');

        if ($type = $request->input('type')) {
            $query->whereHas('roles', function ($query) use ($type) {
                $query->where('name', $type == 'pharmacy' ? 'customer' : strtolower($type));
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

        if($status = $request->input('status')){
            $status = is_array($status) ? array_unique(array_map('trim', array_map('strtoupper', $status))) : array_unique(array_map('trim', array_map('strtoupper', explode(",", $status))));
            $query->whereIn('status', $status);
        }

        $users = $query->whereHas('ownerBusinessType')->latest('id')->paginate();

        return $this->returnJsonResponse(
            message: 'Users successfully fetched.',
            data: UserResource::collection($users)->response()->getData(true)
        );
    }



    /**
     * Admin add a new supplier, vendor, pharmacy.
     *
     * This method validates the incoming request, creates a new new supplier, vendor, pharmacy/
     * using the validated data, and returns a JSON response with the new supplier's, vendor's,
     * pharmacy's details.
     *
     * @param CreateUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateUserRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        $user = $this->userService->store($validated);

        if (!$user) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t add user at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'User created successfully.',
            data: new UserResource($user)
        );
    }

    /**
     * Show a user.
     */
    public function show(ShowUserRequest $request, User $user)
    {
        return $user
            ? $this->returnJsonResponse(
                message: 'User successfully fetched.',
                data: new UserWithBusinessResource($user->load(['businesses', 'actions']))
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

        AuditLogService::log(
            target: $user,
            event: 'User.'.$status,
            action: 'User '.$status,
            description: "User status has been successfully updated to {$status}.",
            crud_type: 'UPDATE',
            properties: []
        );


        $subject = $status == "suspended" ? "Account suspended" : "Account unsuspended";
        $message = $status == "suspended"
            ? "Your account has been suspended. Please contact support for more information."
            : "Your account has been unsuspended. You can now access your account again.";
        $mailable = (new MailMessage)
            ->greeting('Hello '.$user->name)
            ->subject($subject)
            ->line($message)
            ->line('Best Regards,')
            ->line('The 10MG Health Team');

        Notification::route('mail', [
            $user->email => $user->name,
        ])->notify(new UserStatusNotification($mailable));


        if($status == "suspended") {
            (new InAppNotificationService)
                ->forUser($user)->notify(InAppNotificationType::ACCOUNT_SUSPENSION);
        } else{
            (new InAppNotificationService)
                ->forUser($user)->notify(InAppNotificationType::ACCOUNT_UNSUSPENDED);
        }

        return $this->returnJsonResponse(
            message: "User status has been successfully updated to {$status}.",
            data: new UserResource($user->refresh())
        );
    }

    /**
     * Update the specified user's role and other details.
     *
     * Validates the request data, updates the user's role (removes the current role and assigns the new one),
     * and returns a JSON response with the updated user data.
     *
     * @param UpdateUserRequest $request The validated request instance containing the user's updated data.
     * @param User $user The user to be updated.
     * @return \Illuminate\Http\JsonResponse JSON response containing the updated user data and a success message.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        // If no valid data to update, return early with a message.
        if (empty($validated)) {
            return $this->returnJsonResponse(
                message: 'Nothing to update.'
            );
        }

        // If the user is invited and has a role_id to update, update the invite's role_id
        if ($user->invited()->exists() && isset($validated['role_id'])) {
            $user->invited()->update(['role_id' => $validated['role_id']]);
        }

        // If a new role_id is provided in the request, remove the current role and assign the new role
        if (isset($validated['role_id'])) {
            // The new role_id to be assigned
            $newRole = $validated['role_id'];

            // Remove the user's current role (assuming the user has at least one role)
            $user->removeRole($user->roles->first());

            // Assign the new role to the user
            $user->assignRole($newRole);
        }

        // Return a JSON response with the updated user data
        return $this->returnJsonResponse(
            message: "User successfully updated.",
            data: new UserResource($user->refresh())
        );
    }

    public function destroy(DeleteUserRequest $request, User $user)
    {
        $user->forceDelete();

        AuditLogService::log(
            target: $user,
            event: 'User.deleted',
            action: 'User deleted',
            description: "User deleted successfully",
            crud_type: 'DELETE',
            properties: []
        );

        return $this->returnJsonResponse(
            message: 'User successfully deleted.',
        );
    }

}
