<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\LenderKycSession;
use App\Services\Credit\LenderKycTierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class LenderKycController extends Controller
{
    public function __construct(
        private readonly LenderKycTierService $kycTierService,
    ) {}

    /**
     * List all lender KYC sessions
     */
    public function index(Request $request): JsonResponse
    {
        $query = LenderKycSession::with(['lenderBusiness.owner', 'lenderMonoProfile']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by business ID
        if ($request->has('business_id')) {
            $query->where('lender_business_id', $request->business_id);
        }

        // Filter by tier
        if ($request->has('tier')) {
            $query->where('kyc_level', $request->tier);
        }

        $sessions = $query->latest()->paginate(20);

        return response()->json([
            'status' => 'success',
            'message' => 'Lender KYC sessions retrieved successfully',
            'data' => $sessions,
        ]);
    }

    /**
     * Show specific KYC session details
     */
    public function show(LenderKycSession $session): JsonResponse
    {
        $session->load(['lenderBusiness.owner', 'lenderMonoProfile']);

        return response()->json([
            'status' => 'success',
            'message' => 'KYC session details retrieved successfully',
            'data' => $session,
        ]);
    }

    /**
     * Manually update KYC session status
     */
    public function updateStatus(Request $request, LenderKycSession $session): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,successful,cancelled,expired,rejected,awaiting_review',
            'mark_tier_completed' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $updateData = [
                'status' => $validated['status'],
                'meta' => array_merge($session->meta ?? [], [
                    'manual_update' => true,
                    'manual_updated_at' => now()->toIso8601String(),
                    'manual_updated_by' => auth()->id(),
                    'manual_notes' => $validated['notes'] ?? null,
                ]),
            ];

            // If marking as successful, update completion fields
            if ($validated['status'] === 'successful' && $session->kyc_level) {
                $updateData['completed_at'] = now();
                $updateData['verified_at'] = now();
                $updateData['completed_tier'] = $session->kyc_level;

                // Mark tier as completed in business if requested
                if ($validated['mark_tier_completed'] ?? true) {
                    $this->kycTierService->markTierCompleted($session->lenderBusiness, $session->kyc_level);
                }
            }

            $session->update($updateData);

            DB::commit();

            $session->load(['lenderBusiness.owner']);

            return response()->json([
                'status' => 'success',
                'message' => 'KYC session status updated successfully',
                'data' => $session,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update KYC session status',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get KYC statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_sessions' => LenderKycSession::count(),
            'pending_sessions' => LenderKycSession::where('status', 'pending')->count(),
            'successful_sessions' => LenderKycSession::where('status', 'successful')->count(),
            'cancelled_sessions' => LenderKycSession::where('status', 'cancelled')->count(),
            'expired_sessions' => LenderKycSession::where('status', 'expired')->count(),
            'rejected_sessions' => LenderKycSession::where('status', 'rejected')->count(),
            'awaiting_review_sessions' => LenderKycSession::where('status', 'awaiting_review')->count(),
            'sessions_by_tier' => [
                'tier_1' => LenderKycSession::where('kyc_level', 'tier_1')->count(),
                'tier_2' => LenderKycSession::where('kyc_level', 'tier_2')->count(),
                'tier_3' => LenderKycSession::where('kyc_level', 'tier_3')->count(),
            ],
            'completed_by_tier' => [
                'tier_1' => LenderKycSession::where('completed_tier', 'tier_1')->count(),
                'tier_2' => LenderKycSession::where('completed_tier', 'tier_2')->count(),
                'tier_3' => LenderKycSession::where('completed_tier', 'tier_3')->count(),
            ],
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'KYC statistics retrieved successfully',
            'data' => $stats,
        ]);
    }

    /**
     * Manually complete a KYC tier for a business
     */
    public function completeTier(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'tier' => 'required|in:tier_1,tier_2,tier_3',
            'notes' => 'nullable|string|max:1000',
        ]);

        $business = Business::findOrFail($validated['business_id']);

        try {
            // Create a manual KYC session record
            $session = LenderKycSession::create([
                'lender_business_id' => $business->id,
                'prove_id' => 'MANUAL_'.time().'_'.$validated['tier'],
                'reference' => 'MANUAL_'.$business->id.'_'.$validated['tier'].'_'.time(),
                'mono_url' => null,
                'status' => 'successful',
                'kyc_level' => $validated['tier'],
                'completed_tier' => $validated['tier'],
                'completed_at' => now(),
                'verified_at' => now(),
                'bank_accounts' => false,
                'meta' => [
                    'manual_completion' => true,
                    'manual_completed_at' => now()->toIso8601String(),
                    'manual_completed_by' => auth()->id(),
                    'manual_notes' => $validated['notes'] ?? null,
                ],
            ]);

            // Mark tier as completed
            $this->kycTierService->markTierCompleted($business, $validated['tier']);

            return response()->json([
                'status' => 'success',
                'message' => "Tier {$validated['tier']} manually completed for business",
                'data' => [
                    'session' => $session,
                    'business' => $business->load('owner'),
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to manually complete KYC tier',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
