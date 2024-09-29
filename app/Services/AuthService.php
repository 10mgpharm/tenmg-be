<?php

namespace App\Services;

use App\Enums\BusinessStatus;
use App\Enums\BusinessType;
use App\Enums\OtpType;
use App\Helpers\UtilityHelper;
use App\Http\Requests\Auth\CompleteUserSignupRequest;
use App\Http\Requests\Auth\SignupUserRequest;
use App\Http\Requests\AuthProviderRequest;
use App\Http\Resources\UserResource;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Role;
use App\Models\User;
use App\Services\Interfaces\IAuthService;
use Exception;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\PersonalAccessTokenResult;

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

        throw new Exception('Unable to find auth User', Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Get auth id
     *
     * @throws Exception
     */
    public function getId(): int
    {
        try {
            $auth = Auth::user();
            if ($auth instanceof User) {
                return $auth->id;
            }
        } catch (\Throwable) {
        }

        throw new Exception('Unable to find auth User', Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Get auth id
     *
     * @throws Exception
     */
    public function getBusiness(): ?Business
    {
        try {
            $user = Auth::user();
            if ($user instanceof User) {
                return $user->businesses->first();
            }
        } catch (\Throwable $e) {
            Log::error($e);
        }

        throw new Exception('Unable to find auth User', Response::HTTP_UNAUTHORIZED);
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
                'name' => $request['fullname'],
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
    public function verifyUserEmail(User $user, string $code): ?User
    {
        try {
            DB::beginTransaction();

            $otp = (new OtpService)->forUser($user)
                ->validate(OtpType::SIGNUP_EMAIL_VERIFICATION, $code);

            $otp->delete();

            if ($user->hasVerifiedEmail()) {
                return $user;
            }

            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }

            DB::commit();

            return $user;
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

    /**
     * check email exist
     */
    public function emailExist(string $email): ?User
    {
        return User::firstWhere('email', $email);
    }

    /**
     * create new google user with business
     */
    public function googleSignUp(AuthProviderRequest $request): User
    {
        try {
            // Create or find the user
            $user = User::firstOrCreate(
                ['email' => $request['email']],
                [
                    'name' => $request['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make($request['email']),
                    'google_picture_url' => $request['picture'] ?? null,
                ]
            );

            // verify user
            if (! $user->hasVerifiedEmail()) {
                if ($user->markEmailAsVerified()) {
                    event(new Verified($user));
                }
            }

            $businessType = BusinessType::from(strtoupper($request['businessType'] ?: $user->ownerBusinessType->type));
            return $user;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * Complete signup using google
     *
     * @return void
     */
    public function completeGoogleSignUp(CompleteUserSignupRequest $request)
    {
        try {
            $validated = $request->validated();

            DB::beginTransaction();

            $businessType = BusinessType::from(strtoupper($validated['type']));

            $user = $request->user();
            $userRole = $this->resolveSignupRole(type: $businessType);
            $user->assignRole($userRole);

            // create business
            $businessCode = UtilityHelper::generateBusinessCode($validated['name']);
            $adminBusiness = Business::create([
                'name' => $validated['name'],
                'code' => $businessCode,
                'short_name' => $businessCode,
                'owner_id' => $user->id,
                'type' => $businessType,
                'status' => BusinessStatus::PENDING_VERIFICATION->value,
                'contact_email' => $validated['contact_email'],
                'contact_phone' => $validated['contact_phone'],
                'contact_person' => $validated['contact_person'],
                'contact_person_position' => $validated['contact_person_position'],
            ]);

            // map user to business
            BusinessUser::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'business_id' => $adminBusiness->id,
                ],
                ['role_id' => $userRole->id]
            );

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * Complete signup using credentials
     *
     * @return void
     */
    public function completeCredentialSignUp(CompleteUserSignupRequest $request)
    {
        $validated = $request->validated();

        $data = array_filter(array_intersect_key(
            $validated,
            array_flip(['contact_email', 'contact_phone', 'contact_person', 'contact_person_position'])
        ));  // since fillable isn't used.

        $user = $request->user();
        $business = Business::firstWhere('owner_id', $user->id);
        $business->update($data);
    }
}
