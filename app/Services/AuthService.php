<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public bool $isValid = false;

    /**
     * @throws Exception
     */
    public function __construct() {}

    /**
     * @throws Exception
     */
    public function getUser(): User
    {
        try {
            $auth = Auth::user();
            if ($auth instanceof User) {
                return $auth;
            }
        } catch (\Throwable) {
        }

        throw new Exception('User not found', Response::HTTP_NOT_FOUND);
    }
}
