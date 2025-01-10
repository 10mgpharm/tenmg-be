<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\ListUserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use App\Services\Admin\UserService;

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
                $query->where('name', $type == 'pharmacy' ? 'customer' : $type);
            });
        }

        if ($email = $request->input('email')) {
            $query->where('email', $email);
        }

        if (!is_null($request->input('active'))) {
            $query->where('active', $request->input('active'));
        }

        $users = $query->whereHas('ownerBusinessType')->latest()->paginate();

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
}
