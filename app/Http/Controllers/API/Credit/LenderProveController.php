<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\LenderKycSession;
use App\Services\Credit\LenderMonoRegistrationService;
use App\Services\Credit\MonoProveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LenderProveController extends Controller
{
    public function __construct(
        private readonly LenderMonoRegistrationService $lenderMonoRegistrationService,
        private readonly MonoProveService $monoProveService,
    ) {}

    /**
     * Initiate KYC for a lender via Mono Prove.
     * The lender (authenticated user) is the Mono Prove customer.
     */
    public function initiate(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return $this->returnJsonResponse(
                message: 'Unauthorized',
                statusCode: Response::HTTP_UNAUTHORIZED,
                status: 'failed'
            );
        }

        /** @var Business|null $lenderBusiness */
        $lenderBusiness = $user->ownerBusinessType ?? $user->businesses()->first();

        if (! $lenderBusiness) {
            return $this->returnJsonResponse(
                message: 'Lender business not found for this user',
                statusCode: Response::HTTP_BAD_REQUEST,
                status: 'failed'
            );
        }

        $validated = $request->validate([
            'redirect_url' => 'nullable|url|max:500',
            // kyc_level is fixed to tier_1 in the backend
            'bank_accounts' => 'required|boolean',

            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'address' => 'required|string|max:500',
            'identity.type' => 'required|string|in:BVN,NIN,bvn,nin',
            'identity.number' => 'required|string|max:50',
        ]);

        $identityType = $validated['identity']['type'];
        $identityNumber = $validated['identity']['number'];

        $profileData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
        ];

        $profile = $this->lenderMonoRegistrationService->createOrGetLenderMonoCustomer(
            lenderBusiness: $lenderBusiness,
            profileData: $profileData,
            identityNumber: $identityNumber,
            identityType: $identityType,
        );

        if (! $profile) {
            return $this->returnJsonResponse(
                message: 'Unable to create or find lender Mono customer profile',
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
                status: 'failed'
            );
        }

        // Ensure lender is registered as Mono customer before proceeding with Prove
        if (! $profile->mono_customer_id) {
            return $this->returnJsonResponse(
                message: 'Lender must be registered as a Mono customer before initiating KYC. Mono customer registration failed. Please verify your identity details (BVN/NIN, name, email, phone, address) and try again.',
                statusCode: Response::HTTP_BAD_REQUEST,
                status: 'failed',
                data: [
                    'hint' => 'The lender could not be registered on Mono. This may be due to invalid identity details, API configuration issues, or Mono API errors. Please check your credentials and ensure Mono API keys are properly configured.',
                ]
            );
        }

        // Generate a reference if not provided by the frontend
        $reference = $validated['reference'] ?? ('LDR_KYC_'.$lenderBusiness->id.'_'.Str::upper(Str::random(8)));

        $payload = [
            'reference' => $reference,
            'redirect_url' => $validated['redirect_url'] ?? config('services.mono.prove_redirect_url'),
            'kyc_level' => 'tier_1',
            'bank_accounts' => $validated['bank_accounts'],
            'customer' => [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'email' => $validated['email'],
                'identity' => [
                    'number' => $identityNumber,
                    'type' => strtolower($identityType),
                ],
            ],
        ];

        $result = $this->monoProveService->initiateProveSession($payload);

        if (! $result['success']) {
            return $this->returnJsonResponse(
                message: $result['error'] ?? 'Failed to initiate Mono Prove session',
                data: $result,
                statusCode: $result['status_code'] ?? Response::HTTP_BAD_REQUEST,
                status: 'failed'
            );
        }

        $data = $result['data'];

        $session = LenderKycSession::create([
            'lender_business_id' => $lenderBusiness->id,
            'lender_mono_profile_id' => $profile->id,
            'prove_id' => $data['id'] ?? ($data['data']['id'] ?? ''),
            'reference' => $data['reference'] ?? $reference,
            'mono_url' => $data['mono_url'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'kyc_level' => $data['kyc_level'] ?? 'tier_1',
            'bank_accounts' => $data['bank_accounts'] ?? $validated['bank_accounts'],
            'meta' => $data,
        ]);

        return $this->returnJsonResponse(
            message: 'Lender KYC via Mono Prove initiated successfully',
            data: [
                'kyc_session_id' => $session->id,
                'mono_url' => $session->mono_url,
                'prove_id' => $session->prove_id,
                'status' => $session->status,
            ]
        );
    }
}
