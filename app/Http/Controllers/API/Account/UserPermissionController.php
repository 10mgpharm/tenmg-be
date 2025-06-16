<?php

namespace App\Http\Controllers\API\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UserPermissionsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPermissionController extends Controller
{
    public function __invoke(UserPermissionsRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $permissions = $user->getAllPermissions()->pluck('name');
                
            return $this->returnJsonResponse(
                message: 'User permissions successfully fetched.',
                data: $permissions,
            );
        } catch (\Exception $e) {
            return $this->handleErrorResponse($e);
        }
    }
}
