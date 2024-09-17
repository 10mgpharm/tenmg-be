<?php

namespace App\Http\Controllers\API\Auth;

use App\Enums\BusinessStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteUserSignupRequest;
use App\Http\Requests\Auth\SignupUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Business;
use App\Services\Interfaces\IAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SignupUserController extends Controller
{
    /**
     * signup user contructor
     */
    public function __construct(private IAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming signup request.
     *
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(SignupUserRequest $request): JsonResponse
    {
        try {
            $request->validated();
            $user = $this->authService->signUp($request);
            $tokenResult = $user->createToken('Full Access Token', ['full']);

            return (new UserResource($user))
                ->additional([
                    'accessToken' => [
                        'token' => $tokenResult->accessToken,
                        'tokenType' => 'bearer',
                        'expiresAt' => $tokenResult->token->expires_at,
                    ],
                    'message' => 'Sign up successful. Please verify your email using the OTP sent.',
                    'status' => 'success',
                ])
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);

        } catch (\Throwable $th) {
            return $this->handleErrorResponse($th);
        }
    }


    /**
     * Complete signup flow by updating the business details.
     *
     * This method handles the final step in the user signup process, ensuring that 
     * the business details are updated.
     *
     * @throws \Illuminate\Validation\ValidationException If validation fails.
     */
    public function complete(CompleteUserSignupRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $data = array_intersect_key(
            $validated,
            array_flip(['contact_phone', 'contact_person', 'name', 'business_type'])
        );  // since fillable isn't used.

        Business::where('name', $request->input('name'))
            ->where('owner_id', $request->user()->id)
            ->where('status', BusinessStatus::PENDING_VERIFICATION->value)
            ->first()
            ->update($data);

        return response()->json(['message' => 'Signup process completed successfully.'], Response::HTTP_OK);
    }

}
