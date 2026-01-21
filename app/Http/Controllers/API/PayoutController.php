<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Wallet;
use App\Services\Payout\PayoutService;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function __construct(private PayoutService $payoutService) {}

    /**
     * Determine the appropriate wallet type based on user roles
     */
    private function getWalletTypeForUser($user): string
    {
        if ($user->hasRole('admin')) {
            return \App\Enums\WalletType::ADMIN_WALLET->value;
        }

        if ($user->hasRole('vendor') || $user->hasRole('supplier')) {
            return \App\Enums\WalletType::VENDOR_PAYOUT_WALLET->value;
        }

        if ($user->hasRole('lender')) {
            return \App\Enums\WalletType::LENDER_WALLET->value;
        }

        // Default fallback
        return \App\Enums\WalletType::ADMIN_WALLET->value;
    }

    public function listBanks(Request $request)
    {
        $country = $request->query('country', 'NG');
        $currency = $request->query('currency', 'NGN');

        $banks = $this->payoutService->listBanks($country, $currency);

        return $this->returnJsonResponse(
            message: 'Banks fetched successfully.',
            data: $banks
        );
    }

    public function verifyAccount(Request $request)
    {
        $validated = $request->validate([
            'account_number' => 'required|string',
            'bank_code' => 'required|string',
        ]);

        $result = $this->payoutService->verifyAccount(
            $validated['account_number'],
            $validated['bank_code'],
            'NGN', // Always NGN for Nigerian banks
            'nuban' // Always nuban for Nigerian accounts
        );

        return $this->returnJsonResponse(
            message: 'Account verified.',
            data: $result
        );
    }

    public function payout(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'account_number' => 'required|string',
            'bank_code' => 'required|string',
            'bank_name' => 'sometimes|string|nullable',
            'narration' => 'sometimes|string|nullable',
        ]);

        $user = $request->user();
        $business = $user->ownerBusinessType
            ?: $user->businesses()->firstWhere('user_id', $user->id);

        if (! $business instanceof Business) {
            return $this->returnJsonResponse(
                message: 'Business not found for user',
                data: null,
                statusCode: 404
            );
        }

        // Determine the appropriate wallet type based on user roles
        $walletType = $this->getWalletTypeForUser($user);

        // Get the primary wallet for this business and wallet type (prefer NGN currency)
        /** @var Wallet $wallet */
        $wallet = $business->wallets()
            ->with('currency')
            ->where('wallet_type', $walletType)
            ->whereHas('currency', function ($query) {
                $query->where('code', 'NGN');
            })
            ->first();

        if (! $wallet) {
            // Fallback: get any wallet of the correct type
            $wallet = $business->wallets()
                ->with('currency')
                ->where('wallet_type', $walletType)
                ->first();
        }

        if (! $wallet) {
            return $this->returnJsonResponse(
                message: 'No wallet found for your account. Please contact support.',
                data: null,
                statusCode: 404
            );
        }

        $response = $this->payoutService->payoutToBank(
            business: $business,
            wallet: $wallet,
            amount: (float) $validated['amount'],
            bankDetails: [
                'account_number' => $validated['account_number'],
                'account_type' => 'nuban', // Always nuban for Nigerian accounts
                'bank_code' => $validated['bank_code'],
                'bank_name' => $validated['bank_name'] ?? null,
                'country' => 'NG',
                'currency' => $wallet->currency?->code ?? 'NGN', // Always use wallet currency
            ],
            narration: $validated['narration'] ?? null,
            customerEmail: $user->email ?? null,
            customerPhone: $user->phone ?? null
        );

        return $this->returnJsonResponse(
            message: 'Payout initiated',
            data: $response
        );
    }
}
