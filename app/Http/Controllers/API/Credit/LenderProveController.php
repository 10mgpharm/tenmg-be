<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\LenderKycSession;
use App\Services\Credit\LenderKycTierService;
use App\Services\Credit\LenderMonoRegistrationService;
use App\Services\Credit\MonoProveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LenderProveController extends Controller
{
    public function __construct(
        private readonly LenderMonoRegistrationService $lenderMonoRegistrationService,
        private readonly MonoProveService $monoProveService,
        private readonly LenderKycTierService $kycTierService,
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

        // Auto-populate customer data from user and business only
        $validated = $request->validate([
            'redirect_url' => 'nullable|url|max:500',
            'reference' => 'nullable|string|max:255',
            'bank_accounts' => 'required|boolean',
            'identity.type' => 'required|string|in:BVN,NIN,bvn,nin',
            'identity.number' => 'required|string|max:50',
        ]);

        // Auto-populate customer data from user and business
        $customerName = $user->name ?? $lenderBusiness->contact_person;
        $customerEmail = $user->email ?? $lenderBusiness->contact_email;
        $customerPhone = $user->phone ?? $lenderBusiness->contact_phone;
        $customerAddress = $lenderBusiness->address;

        // Validate required fields exist (only name and email are required)
        $missingFields = [];
        if (empty($customerName)) {
            $missingFields[] = 'name (user name or business contact person)';
        }
        if (empty($customerEmail)) {
            $missingFields[] = 'email (user email or business contact email)';
        }
        // Phone and address are optional - bypass if missing

        if (! empty($missingFields)) {
            return $this->returnJsonResponse(
                message: 'Required customer information is missing',
                data: [
                    'error' => 'Missing required fields',
                    'missing_fields' => $missingFields,
                    'hint' => 'Please update your user profile or business information to include: '.implode(', ', $missingFields),
                ],
                statusCode: Response::HTTP_BAD_REQUEST,
                status: 'failed'
            );
        }

        $identityType = $validated['identity']['type'];
        $identityNumber = $validated['identity']['number'];

        $profileData = [
            'name' => $customerName,
            'email' => $customerEmail,
            'phone' => $customerPhone,
            'address' => $customerAddress,
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

        // Log what we're sending to help with debugging
        Log::info('Initiating KYC for lender', [
            'lender_business_id' => $lenderBusiness->id,
            'mono_customer_id' => $profile->mono_customer_id,
            'identity_type' => $identityType,
            'identity_number_masked' => substr($identityNumber, 0, 3).'*****'.substr($identityNumber, -3),
            'profile_data' => [
                'name' => $profileData['name'],
                'email' => $profileData['email'],
                'phone' => $profileData['phone'] ? substr($profileData['phone'], 0, 4).'****'.substr($profileData['phone'], -3) : null,
                'address' => $profileData['address'] ? substr($profileData['address'], 0, 10).'...' : null,
            ],
        ]);

        // Try to ensure lender is registered as Mono customer, but allow KYC to proceed if registration fails
        // Some Mono environments may create customers during Prove flow
        if (! $profile->mono_customer_id) {
            Log::warning('Mono customer registration failed, proceeding with KYC anyway', [
                'lender_business_id' => $lenderBusiness->id,
                'profile_id' => $profile->id,
            ]);

            // For development/testing, you can comment out this return to allow KYC without customer registration
            // return $this->returnJsonResponse(
            //     message: 'Lender must be registered as a Mono customer before initiating KYC. Mono customer registration failed. Please verify your identity details (BVN/NIN, name, email, phone, address) and try again.',
            //     statusCode: Response::HTTP_BAD_REQUEST,
            //     status: 'failed',
            //     data: [
            //         'hint' => 'The lender could not be registered on Mono. This may be due to invalid identity details, API configuration issues, or Mono API errors. Please check your credentials and ensure Mono API keys are properly configured.',
            //     ]
            // );
        }

        // Determine the next tier to initiate dynamically
        $nextTier = $this->kycTierService->getNextTier($lenderBusiness);

        if (! $nextTier) {
            return $this->returnJsonResponse(
                message: 'All KYC tiers have been completed. No further verification is required.',
                statusCode: Response::HTTP_BAD_REQUEST,
                status: 'failed',
                data: [
                    'highest_completed_tier' => $this->kycTierService->getHighestCompletedTier($lenderBusiness),
                ]
            );
        }

        // Validate that the lender can initiate this tier
        if (! $this->kycTierService->canInitiateTier($lenderBusiness, $nextTier)) {
            return $this->returnJsonResponse(
                message: "Cannot initiate {$nextTier}. Please complete the previous tier first.",
                statusCode: Response::HTTP_BAD_REQUEST,
                status: 'failed',
                data: [
                    'required_tier' => $this->kycTierService->getHighestCompletedTier($lenderBusiness),
                    'attempted_tier' => $nextTier,
                ]
            );
        }

        // Generate a reference if not provided by the frontend
        $reference = $validated['reference'] ?? ('LDR_KYC_'.$lenderBusiness->id.'_'.Str::upper(Str::random(8)));

        // Build customer payload - phone and address are optional
        $customerPayload = [
            'name' => $customerName,
            'email' => $customerEmail,
            'identity' => [
                'number' => $identityNumber,
                'type' => strtolower($identityType),
            ],
        ];

        // Only include phone and address if they exist
        if (! empty($customerPhone)) {
            $customerPayload['phone'] = $customerPhone;
        }
        if (! empty($customerAddress)) {
            $customerPayload['address'] = $customerAddress;
        }

        $payload = [
            'reference' => $reference,
            'redirect_url' => $validated['redirect_url'] ?? config('services.mono.prove_redirect_url'),
            'kyc_level' => $nextTier,
            'bank_accounts' => $validated['bank_accounts'],
            'customer' => $customerPayload,
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
            'kyc_level' => $data['kyc_level'] ?? $nextTier,
            'completed_tier' => null, // Will be set when status becomes 'successful'
            'bank_accounts' => $data['bank_accounts'] ?? $validated['bank_accounts'],
            'meta' => $data,
        ]);

        // Return Mono's response structure directly
        $monoResponse = $result['mono_response'] ?? [
            'status' => 'successful',
            'message' => 'Request completed successfully',
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ];

        return response()->json($monoResponse);
    }

    /**
     * Fetch customer details from Mono Prove using reference.
     * GET /api/v1/lender/kyc/prove/customers/{reference}
     * https://docs.mono.co/api/prove/fetch-customer-details
     */
    public function fetchCustomerDetails(string $reference): JsonResponse
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

        // Try to find session in database (optional - for references we created)
        // If not found, we can still fetch from Mono API (for webhook references)
        $session = LenderKycSession::where('reference', $reference)
            ->where('lender_business_id', $lenderBusiness->id)
            ->first();

        // If session exists in our DB, verify it belongs to this lender
        // If not in DB, we'll still try to fetch from Mono (might be from webhook)
        if ($session && $session->lender_business_id !== $lenderBusiness->id) {
            return $this->returnJsonResponse(
                message: 'Unauthorized access to this KYC session',
                data: ['error' => 'This reference does not belong to your business'],
                statusCode: Response::HTTP_FORBIDDEN,
                status: 'failed'
            );
        }

        try {
            // Prepare lender data to use in mock response if needed
            $lenderData = null;
            if ($session) {
                $customerData = $session->meta['customer'] ?? null;
                if ($customerData) {
                    $lenderData = [
                        'name' => $customerData['name'] ?? null,
                        'email' => $customerData['email'] ?? null,
                        'phone' => $customerData['phone'] ?? null,
                        'identity_type' => $customerData['identity']['type'] ?? null,
                        'identity_number' => $customerData['identity']['number'] ?? null,
                    ];
                }
            } else {
                // If no session, try to get from user/business
                $lenderData = [
                    'name' => $user->name ?? $lenderBusiness->contact_person,
                    'email' => $user->email ?? $lenderBusiness->contact_email,
                    'phone' => $user->phone ?? $lenderBusiness->contact_phone,
                ];
            }

            $result = $this->monoProveService->fetchCustomerDetails($reference, $lenderData);

            if (! $result['success']) {
                return $this->returnJsonResponse(
                    message: $result['error'] ?? 'Failed to fetch customer details',
                    data: $result,
                    statusCode: $result['status_code'] ?? Response::HTTP_BAD_REQUEST,
                    status: 'failed'
                );
            }

            // Update session status if session exists in our database
            if ($session && isset($result['data']['status'])) {
                $newStatus = $result['data']['status'];
                $updateData = [
                    'status' => $newStatus,
                    'meta' => array_merge($session->meta ?? [], ['customer_details' => $result['data']]),
                ];

                // If status is successful, mark tier as completed
                if ($newStatus === 'successful' && $session->kyc_level) {
                    $updateData['completed_at'] = now();
                    $updateData['verified_at'] = now();
                    $updateData['completed_tier'] = $session->kyc_level;

                    // Update business with highest completed tier
                    $this->kycTierService->markTierCompleted($lenderBusiness, $session->kyc_level);
                }

                $session->update($updateData);
            }

            // Return Mono's response structure
            $monoResponse = $result['mono_response'] ?? [
                'status' => 'successful',
                'message' => 'Customer details retrieved successfully',
                'timestamp' => now()->toIso8601String(),
                'data' => $result['data'],
            ];

            return response()->json($monoResponse);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Exception while fetching customer details', [
                'reference' => $reference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->returnJsonResponse(
                message: 'Error fetching customer details',
                data: ['error' => $e->getMessage()],
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
                status: 'failed'
            );
        }
    }

    /**
     * Manually update KYC session status (for testing/development)
     * This allows lenders to manually mark their KYC as completed during development
     */
    public function updateSessionStatus(Request $request, string $reference): JsonResponse
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
            'status' => 'required|in:pending,successful,cancelled,expired,rejected',
            'mark_completed' => 'boolean',
        ]);

        $session = LenderKycSession::where('reference', $reference)
            ->where('lender_business_id', $lenderBusiness->id)
            ->first();

        if (! $session) {
            return $this->returnJsonResponse(
                message: 'KYC session not found for this reference',
                statusCode: Response::HTTP_NOT_FOUND,
                status: 'failed'
            );
        }

        $updateData = [
            'status' => $validated['status'],
            'meta' => array_merge($session->meta ?? [], [
                'manual_status_update' => true,
                'manual_updated_at' => now()->toIso8601String(),
                'manual_updated_by_user' => $user->id,
            ]),
        ];

        // If marking as successful and requested to mark completed
        if ($validated['status'] === 'successful' && ($validated['mark_completed'] ?? false) && $session->kyc_level) {
            $updateData['completed_at'] = now();
            $updateData['verified_at'] = now();
            $updateData['completed_tier'] = $session->kyc_level;

            // Mark tier as completed
            $this->kycTierService->markTierCompleted($lenderBusiness, $session->kyc_level);
        }

        $session->update($updateData);

        $session->load(['lenderBusiness']);

        return response()->json([
            'status' => 'success',
            'message' => 'KYC session status updated successfully',
            'data' => [
                'session' => $session,
                'next_tier' => $this->kycTierService->getNextTier($lenderBusiness),
            ],
        ]);
    }
}
