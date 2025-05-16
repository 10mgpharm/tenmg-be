<?php

namespace App\Http\Controllers\API;

use App\Constants\EcommerceWalletConstants;
use App\Enums\OtpType;
use App\Helpers\UtilityHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\WithdrawFundRequest;
use Illuminate\Support\Str;
use App\Models\Business;
use App\Models\EcommercePayment;
use App\Models\EcommerceTransaction;
use App\Models\EcommerceWallet;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\OtpService;
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

                $otp = (new OtpService)->validate(
                    OtpType::WITHDRAW_FUND_TO_BANK_ACCOUNT,
                    $request->input('otp')
                );

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
                    'api-key' => config('services.fincra.secret'),
                    'Content-Type' => 'application/json',
                ])
                ->post(config('services.fincra.url') . '/disbursements/payouts', [
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
                    'business' => config('services.fincra.business_id'),
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


                if (!$response->successful() && config('app.env') !== 'local') {
                    throw new \Exception('Fincra payout failed: ' . $response->body());
                }

                $result = $response->json();
                $reference = $result['reference'] ?? Str::random(24);

                // Deduct from wallet
                $wallet->previous_balance = $wallet->current_balance;
                $wallet->current_balance -= $amount;
                $wallet->save();

                // Log Commerce Transaction
                $transaction = EcommerceTransaction::create([
                    'status' => 'DEBIT',
                    'description' => 'Withdrawal to bank',
                    'supplier_id' => $business->id,
                    'txn_type' => 'DEBIT',
                    'txn_group' => 'WITHDRAW_TO_BANK',
                    'amount' => $amount,
                    'balance_before' => $wallet->previous_balance,
                    'balance_after' => $wallet->current_balance,
                    'status' => 'DEBIT',
                ]);

                // Log Ecommerce Payment
                $payment = EcommercePayment::create([
                    'amount' => $amount,
                    'status' => 'success',
                    'reference' => Str::random(24),
                    'external_reference' => $reference,
                    'customer_id' => $user->id,
                    'amount' => 5000.00,
                    'fee' => 200.00,
                    'total_amount' => 5200.00,
                    'comment' => 'Payout to bank account',
                    'paid_at' => now(),
                    'currency' => 'NGN',
                    'channel' => 'fincra',
                    'meta' => json_encode([
                        'method' => 'fincra_payout',
                        'initiated_by' => 'system',
                        'note' => 'Auto payout'
                    ]),
                    // Polymorphic wallet
                    'wallet_id' => $wallet->id,
                    'wallet_type' => get_class($wallet),
                    'business_id' => $business->id,
                    'ecommerce_transaction_id' => $transaction->id,
                ]);

                // Log 
                AuditLogService::log(
                    target: $wallet,
                    event: 'wallet.withdrawal',
                    action: "Withdrawal to bank",
                    description: "{$user->name} withdraw {$amount} from {$business->name} wallet.",
                    crud_type: 'CREATE', // Use 'UPDATE' for updating actions
                    properties: [
                        'transaction_id' => $transaction->id,
                        'payment_id' => $payment->id,
                        'channel' => 'fincra',
                        'wallet_id' => $wallet->id,
                        'business_id' => $business->id,
                    ]

                );

                // Delete otp.
                $otp->delete();
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
