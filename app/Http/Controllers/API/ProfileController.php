<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Response;
use App\Http\Resources\UserResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileShowRequest;
use Illuminate\Http\Request;

class ProfileController extends Controller
{

    /**
     * Show vendor user information, business info, and account status.
     *
     * @param BusinessTypeRequest $request
     * @param string $businessType
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ProfileShowRequest $request, string $businessType, int $id)
    {
        $user = $request->user();
        return (new UserResource($user))
            ->additional([
                'message' => 'User profile, business details, and account status retrieved successfully.',
                'status' => 'success',
            ])
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
}
