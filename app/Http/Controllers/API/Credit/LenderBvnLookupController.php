<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\LenderBvnLookup;
use App\Services\Credit\MonoBvnLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LenderBvnLookupController extends Controller
{
    public function __construct(
        private readonly MonoBvnLookupService $monoBvnLookupService
    ) {}

    /**
     * Step 1: Initiate BVN Lookup
     * POST /api/v1/lender/credit/bvn-lookup/initiate
     *
     * Request body:
     * {
     *   "bvn": "12345678901",
     *   "scope": "identity" // or "bank_accounts"
     * }
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
            'bvn' => 'required|string|size:11',
            'scope' => 'nullable|string|in:identity,bank_accounts',
        ]);

        $bvn = $validated['bvn'];
        $scope = $validated['scope'] ?? 'identity';

        try {
            $result = $this->monoBvnLookupService->initiateLookup($lenderBusiness, $bvn, $scope);

            if (! $result['success']) {
                return $this->returnJsonResponse(
                    message: $result['error'] ?? 'Failed to initiate BVN lookup',
                    data: $result,
                    statusCode: $result['status_code'] ?? Response::HTTP_BAD_REQUEST,
                    status: 'failed'
                );
            }

            return $this->returnJsonResponse(
                message: 'BVN lookup initiated successfully',
                data: [
                    'session_id' => $result['session_id'],
                    'methods' => $result['methods'],
                    'bvn_lookup_id' => $result['bvn_lookup_id'],
                ]
            );

        } catch (\Exception $e) {
            Log::error('Exception while initiating BVN lookup', [
                'lender_business_id' => $lenderBusiness->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->returnJsonResponse(
                message: 'Error initiating BVN lookup',
                data: ['error' => $e->getMessage()],
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
                status: 'failed'
            );
        }
    }

    /**
     * Step 2: Verify OTP
     * POST /api/v1/lender/credit/bvn-lookup/verify
     *
     * Request body:
     * {
     *   "session_id": "74c8fe70-ea2c-458e-a99f-3f7a6061632c",
     *   "method": "phone", // phone, phone_1, alternate_phone, email
     *   "phone_number": "08123456789" // required if method is alternate_phone
     * }
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'method' => 'required|string|in:phone,phone_1,alternate_phone,email',
            'phone_number' => 'required_if:method,alternate_phone|nullable|string',
        ]);

        $sessionId = $validated['session_id'];
        $method = $validated['method'];
        $phoneNumber = $validated['phone_number'] ?? null;

        // Verify session exists and belongs to authenticated lender
        $bvnLookup = LenderBvnLookup::where('session_id', $sessionId)->first();

        if (! $bvnLookup) {
            return $this->returnJsonResponse(
                message: 'BVN lookup session not found',
                data: ['error' => 'Invalid session ID'],
                statusCode: Response::HTTP_NOT_FOUND,
                status: 'failed'
            );
        }

        // Verify lender owns this lookup
        $user = Auth::user();
        if ($user) {
            $lenderBusiness = $user->ownerBusinessType ?? $user->businesses()->first();
            if ($lenderBusiness && $bvnLookup->lender_business_id !== $lenderBusiness->id) {
                return $this->returnJsonResponse(
                    message: 'Unauthorized access to this BVN lookup session',
                    statusCode: Response::HTTP_FORBIDDEN,
                    status: 'failed'
                );
            }
        }

        try {
            $result = $this->monoBvnLookupService->verifyOtp($sessionId, $method, $phoneNumber);

            if (! $result['success']) {
                return $this->returnJsonResponse(
                    message: $result['error'] ?? 'Failed to verify OTP',
                    data: $result,
                    statusCode: $result['status_code'] ?? Response::HTTP_BAD_REQUEST,
                    status: 'failed'
                );
            }

            return $this->returnJsonResponse(
                message: $result['message'] ?? 'OTP verification initiated successfully',
                data: [
                    'session_id' => $sessionId,
                    'message' => $result['message'],
                ]
            );

        } catch (\Exception $e) {
            Log::error('Exception while verifying BVN lookup OTP', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->returnJsonResponse(
                message: 'Error verifying OTP',
                data: ['error' => $e->getMessage()],
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
                status: 'failed'
            );
        }
    }

    /**
     * Step 3: Fetch BVN Details
     * POST /api/v1/lender/credit/bvn-lookup/details
     *
     * Request body:
     * {
     *   "session_id": "74c8fe70-ea2c-458e-a99f-3f7a6061632c",
     *   "otp": "123456"
     * }
     */
    public function fetchDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $sessionId = $validated['session_id'];
        $otp = $validated['otp'];

        // Verify session exists and belongs to authenticated lender
        $bvnLookup = LenderBvnLookup::where('session_id', $sessionId)->first();

        if (! $bvnLookup) {
            return $this->returnJsonResponse(
                message: 'BVN lookup session not found',
                data: ['error' => 'Invalid session ID'],
                statusCode: Response::HTTP_NOT_FOUND,
                status: 'failed'
            );
        }

        // Verify lender owns this lookup
        $user = Auth::user();
        if ($user) {
            $lenderBusiness = $user->ownerBusinessType ?? $user->businesses()->first();
            if ($lenderBusiness && $bvnLookup->lender_business_id !== $lenderBusiness->id) {
                return $this->returnJsonResponse(
                    message: 'Unauthorized access to this BVN lookup session',
                    statusCode: Response::HTTP_FORBIDDEN,
                    status: 'failed'
                );
            }
        }

        try {
            $result = $this->monoBvnLookupService->fetchDetails($sessionId, $otp);

            if (! $result['success']) {
                return $this->returnJsonResponse(
                    message: $result['error'] ?? 'Failed to fetch BVN details',
                    data: $result,
                    statusCode: $result['status_code'] ?? Response::HTTP_BAD_REQUEST,
                    status: 'failed'
                );
            }

            // Refresh lookup to get updated data
            $bvnLookup->refresh();

            return $this->returnJsonResponse(
                message: 'BVN details fetched successfully',
                data: [
                    'session_id' => $sessionId,
                    'scope' => $bvnLookup->scope,
                    'data' => $result['data'],
                    'bvn_lookup_id' => $bvnLookup->id,
                ]
            );

        } catch (\Exception $e) {
            Log::error('Exception while fetching BVN lookup details', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->returnJsonResponse(
                message: 'Error fetching BVN details',
                data: ['error' => $e->getMessage()],
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
                status: 'failed'
            );
        }
    }

    /**
     * Get BVN Lookup by Session ID
     * GET /api/v1/lender/credit/bvn-lookup/{session_id}
     */
    public function show(string $sessionId): JsonResponse
    {
        $bvnLookup = LenderBvnLookup::where('session_id', $sessionId)->first();

        if (! $bvnLookup) {
            return $this->returnJsonResponse(
                message: 'BVN lookup session not found',
                data: ['error' => 'Invalid session ID'],
                statusCode: Response::HTTP_NOT_FOUND,
                status: 'failed'
            );
        }

        // Verify lender owns this lookup
        $user = Auth::user();
        if ($user) {
            $lenderBusiness = $user->ownerBusinessType ?? $user->businesses()->first();
            if ($lenderBusiness && $bvnLookup->lender_business_id !== $lenderBusiness->id) {
                return $this->returnJsonResponse(
                    message: 'Unauthorized access to this BVN lookup session',
                    statusCode: Response::HTTP_FORBIDDEN,
                    status: 'failed'
                );
            }
        }

        return $this->returnJsonResponse(
            message: 'BVN lookup session retrieved successfully',
            data: [
                'session_id' => $bvnLookup->session_id,
                'scope' => $bvnLookup->scope,
                'status' => $bvnLookup->status,
                'verification_method' => $bvnLookup->verification_method,
                'verification_methods' => $bvnLookup->verification_methods,
                'lookup_data' => $bvnLookup->lookup_data,
                'error_message' => $bvnLookup->error_message,
                'created_at' => $bvnLookup->created_at->toIso8601String(),
                'updated_at' => $bvnLookup->updated_at->toIso8601String(),
            ]
        );
    }
}
