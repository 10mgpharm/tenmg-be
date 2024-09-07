<?php

namespace App\Services;

use App\Enums\BusinessStatus;
use App\Enums\BusinessType;
use App\Helpers\UtilityHelper;
use App\Http\Requests\Auth\SignupUserRequest;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Otp;
use App\Models\Role;
use App\Models\User;
use App\Services\Interfaces\IAuthService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService implements IAuthService
{
    public bool $isValid = false;

    /**
     * @throws Exception
     */
    public function __construct() {}

    /**
     * Get user
     *
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

    /**
     * create new user with business
     */
    public function signUp(SignupUserRequest $request): ?User
    {
        try {
            DB::beginTransaction();

            $businessType = BusinessType::from(strtoupper($request['businessType']));

            // create user
            $user = User::create([
                'name' => $request['name'],
                'email' => $request['email'],
                'password' => Hash::make($request['password']),
            ]);
            $userRole = $this->resolveSignupRole(type: $businessType);
            $user->assignRole($userRole);

            // create business
            $businessCode = UtilityHelper::generateBusinessCode($request['name']);
            $adminBusiness = Business::create([
                'name' => $request['name'],
                'code' => $businessCode,
                'short_name' => $businessCode,
                'owner_id' => $user->id,
                'type' => $businessType,
                'status' => BusinessStatus::PENDING_VERIFICATION->value,
            ]);

            // map user to business
            BusinessUser::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'business_id' => $adminBusiness->id,
                ],
                ['role_id' => $userRole->id]
            );

            $code = UtilityHelper::generateOtp();
            $token = Otp::create([
                'code' => $code,
                'type' => 'Verify Account',
                'user_id' => $user->id,
            ]);

            $user->sendEmailVerification($token->code);

            DB::commit();

            return $user;

        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * return role based on signup type
     */
    public function resolveSignupRole(BusinessType $type): ?Role
    {
        switch ($type) {
            case BusinessType::SUPPLIER:
                return Role::where('name', 'supplier')->first();

            case BusinessType::VENDOR:
                return Role::where('name', 'supplier')->first();

            default:
                // BusinessType::CUSTOMER_PHARMACY
                return Role::where('name', 'customer')->first();
        }
    }
}
