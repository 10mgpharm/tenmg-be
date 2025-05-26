<?php

namespace App\Repositories;

use App\Enums\OtpType;
use App\Models\Business;
use App\Models\CreditLendersWallet;
use App\Models\CreditTransactionHistory;
use App\Models\CreditVendorWallets;
use App\Models\Loan;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorWalletRepository
{

    public function getWalletStats($businessId = null)
    {
        $business_id = null;
        if ($businessId) {
            $business_id = $businessId;
        } else {
            $user = request()->user();
            $business_id = $user->ownerBusinessType?->id
                ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;
        }

        $financials = $this->getBusinessFinancials($business_id);
        $onGoingLoans = $financials['onGoingLoans'];
        $voucherBalance = $financials['voucherBalance'];
        $payoutBalance = $financials['payoutBalance'];

        return $record = [
            'onGoingLoans' => $onGoingLoans,
            'voucherBalance' => $voucherBalance->current_balance ?? 0,
            'payoutBalance' => $payoutBalance->current_balance ?? 0
        ];
    }

    public function getBusinessFinancials($business_id)
    {
        return [
            'onGoingLoans' => Loan::where('business_id', $business_id)
                ->where('status', 'Ongoing')
                ->sum('capital_amount'),

            'voucherBalance' => CreditVendorWallets::where('vendor_id', $business_id)
                ->where('type', 'credit_voucher')
                ->first('current_balance'),

            'payoutBalance' => CreditVendorWallets::where('vendor_id', $business_id)
                ->where('type', 'payout')
                ->first('current_balance')
        ];
    }

    public function getTransactions(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {

        $perPage = request()->query('perPage') ?? 15;
        $business_id = request()->query('businessId');
        $dateFrom = request()->query('dateFrom');
        $dateTo = request()->query('dateTo');
        $status = request()->query('status');

        if ($business_id == null) {
            $user = request()->user();
            $business_id = $user->ownerBusinessType?->id
                ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;
        }

        $query = CreditTransactionHistory::query();

        $query->when(
            isset($dateFrom) && isset($dateTo),
            function ($query) use ($dateFrom, $dateTo) {
                // Parse dates with Carbon to ensure proper format
                $dateFrom = \Carbon\Carbon::parse($dateFrom)->startOfDay();
                $dateTo = \Carbon\Carbon::parse($dateTo)->endOfDay();

                return $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            }
        );

        $query->when(isset($status), function ($query) use ($status) {
            return $query->where('status', $status['status']);
        });

        $query->where('business_id', $business_id)->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    public function initWithdrawals(Request $request)
    {
        try {
            DB::beginTransaction();

            //create a new withdrawal transaction record

            $user = request()->user();
            $business_id = $user->ownerBusinessType?->id
                ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

            //lock the table until process is complete

            $payoutWallet = CreditVendorWallets::where('vendor_id', $business_id)->where('type', 'payout')->lockForUpdate()->first();

            //check if vendor has the amount
            if ($payoutWallet->current_balance < $request->amount) {
                abort(400, "Insufficient funds");
            }

            //deduct amount from vendor payout wallet
            $payoutWallet->prev_balance = $payoutWallet->current_balance;
            $payoutWallet->current_balance -= $request->amount;
            $payoutWallet->save();


            $withdrawal = CreditTransactionHistory::create([
                'business_id' => $business_id,
                'amount' => $request->amount,
                'type' => 'DEBIT',
                'transaction_group' => 'Withdrawal',
                'status' => 'pending_verification',
                'payment_method' => 'fincra',
                'description' => "Withdrawal to bank",
                'meta' => [
                    'bank_account_id' => $request->bankAccountId,
                    'amount' => $request->amount,
                ]
            ]);


            (new OtpService)->forUser($user)
                ->generate(OtpType::WITHDRAW_FUND_TO_BANK_ACCOUNT)
                ->sendMail(OtpType::WITHDRAW_FUND_TO_BANK_ACCOUNT);

            DB::commit();

            return $withdrawal;

        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function withdrawFunds(Request $request)
    {
        //verify id otp is valid

        $creditTransaction = CreditTransactionHistory::where('identifier', $request->reference)->first();
        if($creditTransaction == null) {
            abort(400, 'Transaction not found');
        }

        (new OtpService)->validate(OtpType::WITHDRAW_FUND_TO_BANK_ACCOUNT, $request->otp);

        if (config('app.env') != 'production') {

            $creditTransaction->status = "success";
            $creditTransaction->save();

            return $creditTransaction;
        }

        //check if the amount is available
        $amount = $request->amount;
        return $this->processWithdrawalLive($request, $creditTransaction);
    }

    public function processWithdrawalLive(Request $request, CreditTransactionHistory $creditTransactionHistory)
    {
        $user = $request->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;
        $bankDetails = $creditTransactionHistory->meta['bank_account_id'] ?? null;

        $business = Business::find($business_id);

        $body = [
            'amount' => $creditTransactionHistory->amount,
            'beneficiary' => [
                'accountHolderName' => $bankDetails->account_name,
                'accountNumber' => $bankDetails->account_number,
                'bankCode' => $bankDetails->bank_code,
                'country' => 'NG',
                'firstName' => explode(" ", $user->name)[0],
                'lastName' => explode(" ", $user->name)[1],
                'type' => 'individual',
            ],
            'business' => config('services.fincra.business_id'),
            'customerReference' => $creditTransactionHistory->identifier,
            'description' => 'Withdrawal to bank',
            'destinationCurrency' => 'NGN',
            'paymentDestination' => 'bank_account',
            'sourceCurrency' => 'NGN',
            'sender' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'country' => 'NG',
                'type' => 'business',
            ],
            'narration' => 'Wallet withdrawal',
            'customerName' => $business->name,
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => config('services.fincra.url') . "/disbursements/payouts",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "content-type: application/json",
                'api-key: ' . config('services.fincra.secret'),
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($err) {
            throw new \Exception($err);
        } else {
            if ($statusCode == 200) {
                return $this->processSuccess(json_decode($response), $creditTransactionHistory);
            }
            return $this->changePaymentToPending($creditTransactionHistory->identifier);
            $data = json_decode($response, true);

            if ($data['message'] == "no Route matched with those values") {
                throw new \Exception("No response from Fincra");
            }
        }
    }

    public function processSuccess($response, CreditTransactionHistory $creditTransactionHistory)
    {

        // Update the transaction status to success
        $creditTransactionHistory->status = 'success';
        $creditTransactionHistory->meta = $response;
        $creditTransactionHistory->save();

        return $creditTransactionHistory;
    }

    public function changePaymentToPending($ref)
    {
        $creditTransactionHistory = CreditTransactionHistory::where('identifier', $ref)->first();
        $creditTransactionHistory->status = 'pending';
        $creditTransactionHistory->save();

        return $creditTransactionHistory;
    }
}
