<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\DB;

class PasswordUpdateController extends Controller
{
    /**
     * Update the authenticated user password.
     *
     * @param \App\Http\Requests\PasswordUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(PasswordUpdateRequest $request): JsonResponse
    {
        try {
            DB::transaction(function () use ($request) {
                $user = $request->user();
                $password = $request->input('newPassword') ?: $request->input('currentPassword');
                
                // Update password
                $user->update([
                    'password' => Hash::make($password),
                    'use_2fa' => boolval($request->input('use2fa')),
                ]);

                // Check if the user has a current token
                if ($currentToken = $request->user()->token()) {

                    // Delete all tokens except the current one
                    $user->tokens()->where('id', '!=', $currentToken->id)->delete();
                }
            });

            return $this->returnJsonResponse(
                message: 'Password updated successfully.',
                data: new UserResource($request->user()->refresh())
            );
        } catch (\Throwable $th) {
            return $this->handleErrorResponse($th);
        }
    }
}
