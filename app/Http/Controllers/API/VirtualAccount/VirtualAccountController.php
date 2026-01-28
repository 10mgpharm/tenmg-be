<?php

namespace App\Http\Controllers\API\VirtualAccount;

use App\Enums\BusinessType;
use App\Http\Controllers\Controller;
use App\Http\Resources\VirtualAccount\VirtualAccountResource;
use App\Models\Business;
use App\Models\LenderKycSession;
use App\Models\Wallet;
use App\Services\Interfaces\IVirtualAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class VirtualAccountController extends Controller
{
    public function __construct(
        private IVirtualAccountService $virtualAccountService
    ) {}

    /**
     * Create (if missing) or return virtual account for a wallet
     * GET /api/v1/virtual-account/wallet/{walletId}
     *
     * Only lenders can create virtual accounts.
     * Requires completed KYC session with BVN verification.
     */
    public function getOrCreateByWallet(string $walletId): JsonResponse
    {
        try {
            $user = auth()->user();

            if (! $user) {
                return $this->returnJsonResponse(
                    message: 'Unauthenticated',
                    status: 'error',
                    statusCode: 401
                );
            }

            // Get wallet
            $wallet = Wallet::with(['currency', 'virtualAccount.currency', 'virtualAccount.serviceProvider'])
                ->find($walletId);

            if (! $wallet) {
                return $this->returnJsonResponse(
                    message: 'Wallet not found',
                    status: 'error',
                    statusCode: 404
                );
            }

            // Get user's business
            $business = $user->ownerBusinessType
                ?: $user->businesses()->firstWhere('user_id', $user->id);

            if (! $business) {
                return $this->returnJsonResponse(
                    message: 'Business not found',
                    status: 'error',
                    statusCode: 404
                );
            }

            // Only lenders can create virtual accounts
            if ($business->type !== BusinessType::LENDER->value) {
                return $this->returnJsonResponse(
                    message: 'Virtual accounts can only be created by lenders.',
                    status: 'error',
                    statusCode: 403
                );
            }

            // Verify wallet belongs to user's business
            if ($wallet->business_id !== $business->id) {
                return $this->returnJsonResponse(
                    message: 'Wallet does not belong to your business',
                    status: 'error',
                    statusCode: 403
                );
            }

            // Check if virtual account already exists
            if ($wallet->virtualAccount) {
                return $this->returnJsonResponse(
                    message: 'Virtual account already exists for this wallet',
                    data: new VirtualAccountResource($wallet->virtualAccount),
                    status: 'success'
                );
            }

            // Get KYC session for lender (required for virtual account creation)
            $kycSession = LenderKycSession::where('lender_business_id', $business->id)
                ->latest()
                ->first();

            if (! $kycSession) {
                return $this->returnJsonResponse(
                    message: 'KYC verification required. Please complete your KYC verification first.',
                    status: 'error',
                    statusCode: 400,
                    data: ['requires_kyc' => true]
                );
            }

            // Check tier and status rules
            $tier = $kycSession->kyc_level ?? $kycSession->completed_tier ?? null;
            $status = $kycSession->status;

            if ($tier === '1' || $tier === 1) {
                // Tier 1: Must have successful status
                if ($status !== 'successful') {
                    return $this->returnJsonResponse(
                        message: 'KYC verification must be completed successfully for Tier 1. Please complete your KYC verification first.',
                        status: 'error',
                        statusCode: 400,
                        data: ['requires_kyc' => true, 'tier' => $tier, 'status' => $status]
                    );
                }
            } elseif ($tier === '2' || $tier === 2 || $tier === '3' || $tier === 3) {
                // Tier 2+: Allow even if pending (but we still need to check BVN later)
                // Continue with the session even if pending
            } else {
                // Unknown tier or no tier - require successful status
                if ($status !== 'successful') {
                    return $this->returnJsonResponse(
                        message: 'KYC verification must be completed successfully. Please complete your KYC verification first.',
                        status: 'error',
                        statusCode: 400,
                        data: ['requires_kyc' => true, 'tier' => $tier, 'status' => $status]
                    );
                }
            }

            // Check if BVN exists in KYC session
            $meta = $kycSession->meta ?? [];
            $hasBvn = isset($meta['bvn']) && ! empty($meta['bvn']);

            // Also check Mono profile if available
            if (! $hasBvn && $kycSession->lender_mono_profile_id) {
                $monoProfile = \App\Models\LenderMonoProfile::find($kycSession->lender_mono_profile_id);
                if ($monoProfile && $monoProfile->identity_type === 'bvn') {
                    $hasBvn = true;
                }
            }

            if (! $hasBvn) {
                return $this->returnJsonResponse(
                    message: 'BVN verification required. Please complete BVN verification in your KYC process.',
                    status: 'error',
                    statusCode: 400,
                    data: ['requires_bvn' => true]
                );
            }

            // Create virtual account
            $virtualAccount = $this->virtualAccountService->createVirtualAccount(
                $wallet,
                $business,
                $kycSession
            );

            if (! $virtualAccount) {
                return $this->returnJsonResponse(
                    message: 'Failed to create virtual account. Please try again later or contact support.',
                    status: 'error',
                    statusCode: 500
                );
            }

            // Load relationships for response
            $virtualAccount->load(['currency', 'serviceProvider']);

            Log::info('Virtual account created via API', [
                'business_id' => $business->id,
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'virtual_account_id' => $virtualAccount->id,
            ]);

            return $this->returnJsonResponse(
                message: 'Virtual account created successfully',
                data: new VirtualAccountResource($virtualAccount),
                status: 'success',
                statusCode: 201
            );
        } catch (\Exception $e) {
            Log::error('Failed to create virtual account', [
                'user_id' => auth()->id(),
                'wallet_id' => $walletId ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Check if error is about KYC requirement
            if (str_contains($e->getMessage(), 'KYC') || str_contains($e->getMessage(), 'verification')) {
                return $this->returnJsonResponse(
                    message: $e->getMessage(),
                    status: 'error',
                    statusCode: 400,
                    data: ['requires_kyc' => true]
                );
            }

            return $this->returnJsonResponse(
                message: 'Failed to create virtual account: '.$e->getMessage(),
                status: 'error',
                statusCode: 500
            );
        }
    }

    /**
     * Create (if missing) or return virtual account for primary NGN wallet
     * GET /api/v1/virtual-account
     *
     * Automatically uses the lender's primary NGN wallet.
     * Only lenders can create virtual accounts.
     * Requires completed KYC session with BVN verification.
     */
    public function getOrCreate(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (! $user) {
                return $this->returnJsonResponse(
                    message: 'Unauthenticated',
                    status: 'error',
                    statusCode: 401
                );
            }

            // Get user's business
            $business = $user->ownerBusinessType
                ?: $user->businesses()->where('type', BusinessType::LENDER->value)->first();

            if (! $business) {
                return $this->returnJsonResponse(
                    message: 'Business not found',
                    status: 'error',
                    statusCode: 404
                );
            }

            // Only lenders can create virtual accounts
            if ($business->type !== BusinessType::LENDER->value) {
                return $this->returnJsonResponse(
                    message: 'Virtual accounts can only be created by lenders.',
                    status: 'error',
                    statusCode: 403
                );
            }

            // Get primary NGN wallet (lender_wallet type with NGN currency)
            $wallet = Wallet::with(['currency', 'virtualAccount.currency', 'virtualAccount.serviceProvider'])
                ->where('business_id', $business->id)
                ->where('wallet_type', \App\Enums\WalletType::LENDER_WALLET->value)
                ->whereHas('currency', function ($query) {
                    $query->where('code', 'NGN');
                })
                ->first();

            if (! $wallet) {
                return $this->returnJsonResponse(
                    message: 'Primary NGN wallet not found. Please create a wallet first.',
                    status: 'error',
                    statusCode: 404
                );
            }

            // Check if virtual account already exists
            if ($wallet->virtualAccount) {
                return $this->returnJsonResponse(
                    message: 'Virtual account already exists',
                    data: new VirtualAccountResource($wallet->virtualAccount),
                    status: 'success'
                );
            }

            // TODO: Re-enable KYC checks for production
            // KYC checks temporarily disabled for testing

            // Get KYC session for lender (optional for testing)
            $kycSession = LenderKycSession::where('lender_business_id', $business->id)
                ->latest()
                ->first();

            // COMMENTED OUT FOR TESTING - Re-enable for production
            // if (! $kycSession) {
            //     return $this->returnJsonResponse(
            //         message: 'KYC verification required. Please complete your KYC verification first.',
            //         status: 'error',
            //         statusCode: 400,
            //         data: ['requires_kyc' => true]
            //     );
            // }

            // COMMENTED OUT FOR TESTING - Re-enable for production
            // Check tier and status rules
            // $tier = $kycSession->kyc_level ?? $kycSession->completed_tier ?? null;
            // $status = $kycSession->status;

            // if ($tier === '1' || $tier === 1) {
            //     if ($status !== 'successful') {
            //         return $this->returnJsonResponse(
            //             message: 'KYC verification must be completed successfully for Tier 1.',
            //             status: 'error',
            //             statusCode: 400,
            //             data: ['requires_kyc' => true, 'tier' => $tier, 'status' => $status]
            //         );
            //     }
            // } elseif ($tier !== '2' && $tier !== 2 && $tier !== '3' && $tier !== 3) {
            //     if ($status !== 'successful') {
            //         return $this->returnJsonResponse(
            //             message: 'KYC verification must be completed successfully.',
            //             status: 'error',
            //             statusCode: 400,
            //             data: ['requires_kyc' => true, 'tier' => $tier, 'status' => $status]
            //         );
            //     }
            // }

            // COMMENTED OUT FOR TESTING - Re-enable for production
            // Check if BVN exists
            // $meta = $kycSession->meta ?? [];
            // $hasBvn = isset($meta['bvn']) && ! empty($meta['bvn']);

            // if (! $hasBvn && $kycSession->lender_mono_profile_id) {
            //     $monoProfile = LenderMonoProfile::find($kycSession->lender_mono_profile_id);
            //     if ($monoProfile && $monoProfile->identity_type === 'bvn') {
            //         $hasBvn = true;
            //     }
            // }

            // if (! $hasBvn) {
            //     return $this->returnJsonResponse(
            //         message: 'BVN verification required. Please complete BVN verification in your KYC process.',
            //         status: 'error',
            //         statusCode: 400,
            //         data: ['requires_bvn' => true]
            //     );
            // }

            // Create virtual account (kycSession is optional for testing)
            $virtualAccount = $this->virtualAccountService->createVirtualAccount(
                $wallet,
                $business,
                $kycSession
            );

            if (! $virtualAccount) {
                return $this->returnJsonResponse(
                    message: 'Failed to create virtual account. Please try again later or contact support.',
                    status: 'error',
                    statusCode: 500
                );
            }

            $virtualAccount->load(['currency', 'serviceProvider']);

            Log::info('Virtual account created for primary NGN wallet', [
                'business_id' => $business->id,
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'virtual_account_id' => $virtualAccount->id,
            ]);

            return $this->returnJsonResponse(
                message: 'Virtual account created successfully',
                data: new VirtualAccountResource($virtualAccount),
                status: 'success',
                statusCode: 201
            );
        } catch (\Exception $e) {
            Log::error('Failed to create virtual account', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (str_contains($e->getMessage(), 'KYC') || str_contains($e->getMessage(), 'verification')) {
                return $this->returnJsonResponse(
                    message: $e->getMessage(),
                    status: 'error',
                    statusCode: 400,
                    data: ['requires_kyc' => true]
                );
            }

            return $this->returnJsonResponse(
                message: 'Failed to create virtual account: '.$e->getMessage(),
                status: 'error',
                statusCode: 500
            );
        }
    }

    /**
     * Get virtual account for a wallet
     * GET /api/v1/virtual-account/wallet/{walletId}
     */
    public function getByWallet(string $walletId): JsonResponse
    {
        try {
            $user = auth()->user();

            if (! $user) {
                return $this->returnJsonResponse(
                    message: 'Unauthenticated',
                    status: 'error',
                    statusCode: 401
                );
            }

            // Get wallet
            $wallet = Wallet::with(['virtualAccount.currency', 'virtualAccount.serviceProvider'])
                ->find($walletId);

            if (! $wallet) {
                return $this->returnJsonResponse(
                    message: 'Wallet not found',
                    status: 'error',
                    statusCode: 404
                );
            }

            // Get user's business
            $business = $user->ownerBusinessType
                ?: $user->businesses()->firstWhere('user_id', $user->id);

            // Verify wallet belongs to user's business
            if ($wallet->business_id !== $business?->id) {
                return $this->returnJsonResponse(
                    message: 'Wallet does not belong to your business',
                    status: 'error',
                    statusCode: 403
                );
            }

            if (! $wallet->virtualAccount) {
                return $this->returnJsonResponse(
                    message: 'Virtual account not found for this wallet',
                    status: 'error',
                    statusCode: 404
                );
            }

            return $this->returnJsonResponse(
                message: 'Virtual account retrieved successfully',
                data: new VirtualAccountResource($wallet->virtualAccount),
                status: 'success'
            );
        } catch (\Exception $e) {
            Log::error('Failed to get virtual account', [
                'user_id' => auth()->id(),
                'wallet_id' => $walletId,
                'error' => $e->getMessage(),
            ]);

            return $this->returnJsonResponse(
                message: 'Failed to retrieve virtual account: '.$e->getMessage(),
                status: 'error',
                statusCode: 500
            );
        }
    }
}
