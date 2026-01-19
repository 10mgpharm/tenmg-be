<?php

namespace App\Http\Controllers\API\Wallet;

use App\Enums\BusinessType;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Wallet\WalletResource;
use App\Models\Currency;
use App\Services\Interfaces\IWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    public function __construct(
        private IWalletService $walletService
    ) {}

    /**
     * Wallets for lender (NGN and USD) (creates if missing, otherwise returns existing)
     * GET /api/v1/lender/wallet
     */
    public function createLenderWallets(): JsonResponse
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

            if (! $business || $business->type !== BusinessType::LENDER->value) {
                return $this->returnJsonResponse(
                    message: 'Lender business not found',
                    status: 'error',
                    statusCode: 404
                );
            }

            // Get NGN and USD currencies
            $currencies = Currency::whereIn('code', ['NGN', 'USD'])
                ->where('is_active', true)
                ->get();

            if ($currencies->isEmpty()) {
                return $this->returnJsonResponse(
                    message: 'NGN or USD currencies not found',
                    status: 'error',
                    statusCode: 404
                );
            }

            // Check if wallets already exist for both currencies
            $existingWallets = $business->wallets()
                ->where('wallet_type', WalletType::LENDER_WALLET->value)
                ->whereIn('currency_id', $currencies->pluck('id'))
                ->with('currency')
                ->get();

            $existingCurrencyIds = $existingWallets->pluck('currency_id')->toArray();
            $allCurrencyIds = $currencies->pluck('id')->toArray();

            // Check if all wallets already exist
            if (count($existingWallets) === count($currencies) &&
                empty(array_diff($allCurrencyIds, $existingCurrencyIds))) {
                return $this->returnJsonResponse(
                    message: 'Lender wallets already exist',
                    data: WalletResource::collection($existingWallets),
                    status: 'success'
                );
            }

            // Create missing wallets
            $newWallets = [];
            foreach ($currencies as $currency) {
                // Check if wallet already exists for this currency
                $existingWallet = $existingWallets->firstWhere('currency_id', $currency->id);

                if ($existingWallet) {
                    $newWallets[] = $existingWallet;
                } else {
                    // Create wallet if it doesn't exist
                    $wallet = $this->walletService->createSecondaryWallet(
                        $business,
                        $currency->code,
                        WalletType::LENDER_WALLET
                    );
                    $wallet->load('currency');
                    $newWallets[] = $wallet;
                }
            }

            Log::info('Lender wallets processed via API', [
                'business_id' => $business->id,
                'user_id' => $user->id,
                'existing_wallets' => $existingWallets->count(),
                'total_wallets' => count($newWallets),
            ]);

            return $this->returnJsonResponse(
                message: 'Lender wallets ready',
                data: WalletResource::collection($newWallets),
                status: 'success'
            );
        } catch (\Exception $e) {
            Log::error('Failed to create lender wallets', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->returnJsonResponse(
                message: 'Failed to create lender wallets: '.$e->getMessage(),
                status: 'error',
                statusCode: 500
            );
        }
    }

    /**
     * Wallets for vendor (NGN and USD) (creates if missing, otherwise returns existing)
     * GET /api/v1/vendor/wallet
     */
    public function createVendorWallets(): JsonResponse
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
                ?: $user->businesses()->where('type', BusinessType::VENDOR->value)->first();

            if (! $business || $business->type !== BusinessType::VENDOR->value) {
                return $this->returnJsonResponse(
                    message: 'Vendor business not found',
                    status: 'error',
                    statusCode: 404
                );
            }

            // Get NGN and USD currencies
            $currencies = Currency::whereIn('code', ['NGN', 'USD'])
                ->where('is_active', true)
                ->get();

            if ($currencies->isEmpty()) {
                return $this->returnJsonResponse(
                    message: 'NGN or USD currencies not found',
                    status: 'error',
                    statusCode: 404
                );
            }

            // Check if wallets already exist for both currencies
            $existingWallets = $business->wallets()
                ->where('wallet_type', WalletType::VENDOR_PAYOUT_WALLET->value)
                ->whereIn('currency_id', $currencies->pluck('id'))
                ->with('currency')
                ->get();

            $existingCurrencyIds = $existingWallets->pluck('currency_id')->toArray();
            $allCurrencyIds = $currencies->pluck('id')->toArray();

            // Check if all wallets already exist
            if (count($existingWallets) === count($currencies) &&
                empty(array_diff($allCurrencyIds, $existingCurrencyIds))) {
                return $this->returnJsonResponse(
                    message: 'Vendor wallets already exist',
                    data: WalletResource::collection($existingWallets),
                    status: 'success'
                );
            }

            // Create missing wallets
            $newWallets = [];
            foreach ($currencies as $currency) {
                // Check if wallet already exists for this currency
                $existingWallet = $existingWallets->firstWhere('currency_id', $currency->id);

                if ($existingWallet) {
                    $newWallets[] = $existingWallet;
                } else {
                    // Create wallet if it doesn't exist
                    $wallet = $this->walletService->createSecondaryWallet(
                        $business,
                        $currency->code,
                        WalletType::VENDOR_PAYOUT_WALLET
                    );
                    $wallet->load('currency');
                    $newWallets[] = $wallet;
                }
            }

            Log::info('Vendor wallets processed via API', [
                'business_id' => $business->id,
                'user_id' => $user->id,
                'existing_wallets' => $existingWallets->count(),
                'total_wallets' => count($newWallets),
            ]);

            return $this->returnJsonResponse(
                message: 'Vendor wallets ready',
                data: WalletResource::collection($newWallets),
                status: 'success'
            );
        } catch (\Exception $e) {
            Log::error('Failed to create vendor wallets', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->returnJsonResponse(
                message: 'Failed to create vendor wallets: '.$e->getMessage(),
                status: 'error',
                statusCode: 500
            );
        }
    }
}
