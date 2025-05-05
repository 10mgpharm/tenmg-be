<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\WithdrawFundRequest;
use Illuminate\Support\Str;
use App\Models\Business;
use App\Models\EcommercePayment;
use App\Models\EcommerceTransaction;
use App\Models\EcommerceWallet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WithdrawFundController extends Controller
{

    public function __invoke(WithdrawFundRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $user = $request->user();
                $business = $user->ownerBusinessType
                    ?: $user->businesses()->firstWhere('user_id', $user->id);

                $wallet = EcommerceWallet::with('bankAccount')
                    ->where('business_id', $business->id)
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                $amount = $request->amount;
                $parts = explode(' ', $user->name);

                // Fincra API call
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.fincra.secret_key'),
                    'api-key' => config('services.fincra.secret'),
                    'Content-Type' => 'application/json',
                ])->post(config('services.fincra.url') . '/disbursements/payouts', [
                    'amount' => $amount,
                    'beneficiary' => [
                        'accountHolderName' => $user->name,
                        'accountNumber' => $wallet->bankAccount->account_number,
                        'bankCode' => $wallet->bankAccount->bank_code,
                        'country' => 'NG',
                        'firstName' => reset(($parts)),
                        'lastName' => last(($parts)),
                        'type' => 'individual',
                    ],
                    'business' => '{{Your Business ID}}',
                    'customerReference' => (string) Str::uuid(),
                    'description' => 'Withdrawal to bank',
                    'destinationCurrency' => 'NGN',
                    'paymentDestination' => 'bank_account',
                    'sourceCurrency' => 'NGN',
                    'sender' => [
                        'name' => $business->name,
                        'email' => $business->email,
                        'phone' => $business->phone,
                        'country' => 'NG',
                        'type' => 'business',
                    ],
                    'narration' => 'Wallet withdrawal',
                    'customerName' => $business->name,
                ]);

                if (!$response->successful()) {
                    throw new \Exception('Fincra payout failed: ' . $response->body());
                }

                $reference = $response['reference'] ?? uniqid('fallback_', true);

                // Log Commerce Transaction
                $transaction = EcommerceTransaction::create([
                    'business_id' => $business->id,
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'reference' => $reference,
                    'type' => 'withdrawal',
                    'status' => 'success',
                    'description' => 'Withdrawal to bank',
                ]);

                // Log Ecommerce Payment
                EcommercePayment::create([
                    'business_id' => $business->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $amount,
                    'reference' => $reference,
                    'method' => 'fincra_payout',
                    'status' => 'success',
                    'commerce_transaction_id' => $transaction->id,
                ]);

                // Deduct from wallet
                $wallet->previous_balance = $wallet->current_balance;
                $wallet->current_balance -= $amount;
                $wallet->save();
            });

            return $this->returnJsonResponse(
                message: 'Withdrawal successful.',
                data: null,
                statusCode: Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            report($th);
            return $this->returnJsonResponse(
                message: 'An error occurred while processing your request.',
                data: null,
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
