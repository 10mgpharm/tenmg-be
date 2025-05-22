<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileShowRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Response;

class ProfileController extends Controller
{
    /**
     * Show vendor user information, business info, and account status.
     *
     * @param  BusinessTypeRequest  $request
     * @param  string  $businessType
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ProfileShowRequest $request)
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
