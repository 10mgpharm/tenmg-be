<?php

namespace App\Services;

use App\Enums\BusinessStatus;
use App\Enums\BusinessType;
use App\Enums\OtpType;
use App\Helpers\UtilityHelper;
use App\Http\Requests\Auth\SignupUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Otp;
use App\Models\Role;
use App\Models\User;
use App\Services\Interfaces\IAuthService;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\PersonalAccessTokenResult;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AuthService implements IAuthService
{
    public const TOKEN_EXPIRED_AT = 15;

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

            (new OtpService)->forUser($user)
            ->generate(OtpType::SIGNUP_EMAIL_VERIFICATION)
            ->sendMail(OtpType::SIGNUP_EMAIL_VERIFICATION);

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

    /**
     * verifyUserEmail
     */
    public function verifyUserEmail(User $user, string $code): ?JsonResponse
    {
        try {
            DB::beginTransaction();

            $otp = (new OtpService)->forUser($user)
            ->validate(OtpType::SIGNUP_EMAIL_VERIFICATION, $code);
            
            $otp->delete();

            if ($user->hasVerifiedEmail()) {
                $user->token()->revoke();
                $tokenResult = $user->createToken('Full Access Token', ['full']);

                return $this->returnAuthResponse(
                    user: $user,
                    tokenResult: $tokenResult,
                    message: 'Account verified',
                    statusCode: Response::HTTP_OK
                );
            }

            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }

            $user->token()->revoke();
            $tokenResult = $user->createToken('Full Access Token', ['full']);

            DB::commit();

            return $this->returnAuthResponse(
                user: $user,
                tokenResult: $tokenResult,
                statusCode: Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * Return auth response
     */
    public function returnAuthResponse(User $user, PersonalAccessTokenResult $tokenResult, string $message = 'Sign in successful.', int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return (new UserResource($user))
            ->additional([
                'accessToken' => [
                    'token' => $tokenResult->accessToken,
                    'tokenType' => 'bearer',
                    'expiresAt' => $tokenResult->token->expires_at,
                ],
                'message' => $message,
                'status' => 'success',
            ])
            ->response()
            ->setStatusCode($statusCode);
    }
}
