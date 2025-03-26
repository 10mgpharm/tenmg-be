<?php

namespace App\Repositories;

use App\Models\Business;
use App\Models\CreditLendersWallet;
use App\Models\CreditTransactionHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LenderDashboardRepository
{
    public function getDashboardStats()
    {
        $user = request()->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $business = Business::find($business_id);

        return $business;
    }

    public function getChartStats()
    {
        $user = request()->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $business = Business::find($business_id);

        return $business;
    }

    public function initializeDeposit(Request $request)
    {

        $user = $request->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;
        $lenderDepositWallet = Business::find($business_id)->lendersWallet;
        //create a pending transaction
        $transaction = CreditTransactionHistory::create([
            'business_id' => $business_id,
            'amount' => $request->amount,
            'status' => 'initiated',
            'type' => 'CREDIT',
            'transaction_group' => 'deposit',
            'wallet_id' => $lenderDepositWallet->id,
            'description' => 'Deposit to lender wallet',
            'payment_method' => 'fincra'
        ]);

        return $transaction;

    }

    public function cancelDepositPayment($ref)
    {
        $transaction = CreditTransactionHistory::where('identifier', $ref)->where('transaction_group', 'deposit')->first();

        if ($transaction) {
            $transaction->status = 'cancelled';
            $transaction->save();
        }else{
            throw new \Exception('Transaction not found');
        }

        return $transaction;
    }

    public function completeWalletDeposit($data)
    {
        $body = $data->data;
        $merchantReference = $body->merchantReference;

        $transaction = CreditTransactionHistory::where('identifier', $merchantReference)->where('transaction_group', 'deposit')->first();

        if($transaction->status == 'success'){
            return;
        }


        if ($transaction) {
            $transaction->status = 'success';
            $transaction->reference = $body->reference;
            $transaction->meta = json_encode($body);
            $transaction->save();

            //update the wallet balance
            $wallet = CreditLendersWallet::where('lender_id', $transaction->business_id)->where('type', 'deposit')->first();
            $wallet->prev_balance = $wallet->current_balance;
            $wallet->current_balance += $transaction->amount;
            $wallet->last_transaction_ref = $body->reference;
            $wallet->save();
        }

        return $transaction;
    }

    public function generateStatement(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $user = $request->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $query = CreditTransactionHistory::query();

        $dateFrom = $request->input('dateFrom');
        $dateTo = $request->input('dateTo');

        $query->when(isset($dateFrom) && isset($dateTo), function ($query) use ($dateFrom, $dateTo) {
            return $query->whereBetween('credit_transaction_histories.created_at', [$dateFrom, $dateTo]);
        });

        return $query->where('business_id', $business_id)->orderBy('created_at', 'desc');

    }

    public function withdrawFunds(Request $request)
    {
        $user = $request->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        //check if the amount is available
        $amount = $request->amount;
        $wallet = CreditLendersWallet::where('lender_id', $business_id)->where('type', 'investment')->first();
        if ($wallet->current_balance < $amount) {
            throw new \Exception('Insufficient funds');
        }

        if (config('app.env') != 'production') {
            $transactionId = rand(10000, 99999);

            $transaction = CreditTransactionHistory::create([
                'business_id' => $business_id,
                'amount' => $amount,
                'status' => 'success',
                'type' => 'DEBIT',
                'transaction_group' => 'withdrawal',
                'wallet_id' => $wallet->id,
                'description' => 'Withdrawal from investment wallet',
                'payment_method' => 'fincra',
                'reference' => "fcr-".$transactionId."-test"
            ]);
            //update the wallet balance
            $wallet->prev_balance = $wallet->current_balance;
            $wallet->current_balance -= $amount;
            $wallet->last_transaction_ref = $transactionId."-test";
            $wallet->save();

            return $transaction;
        }




    }

    public function processWithdrawalLive(Request $request, $wallet)
    {
        $user = $request->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $transaction = CreditTransactionHistory::create([
            'business_id' => $business_id,
            'amount' => $request->amount,
            'status' => 'initiated',
            'type' => 'DEBIT',
            'transaction_group' => 'withdrawal',
            'wallet_id' => $wallet->id,
            'description' => 'Withdrawal from investment wallet',
            'payment_method' => 'fincra',
        ]);

        $body = [
            'sourceCurrency' => 'NGN',
            'destinationCurrency' => 'NGN',
            'amount' => $request->amount,
            'description' => 'Withdrawal',
            'customerReference' => $transaction->identifier,
            'beneficiary' => [
                'firstName' => explode(" ", $user->name)[0],
                'lastName' => explode(" ", $user->name)[1],
                'email' => $user->email,
                'type' => 'individual',
                'accountHolderName' => $request->accountName,
                'accountNumber' => $request->accountNumber,
                'mobileMoneyCode' => '901',
                'country' => 'NGN',
                'bankCode' => $request->bankCode,
                'sortCode' => '9090',
                'registrationNumber' => 'A909'
            ],
            'paymentDestination' => 'bank_account',
            'business' => '8999'
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
        CURLOPT_URL => config('services.fincra.url')."/disbursements/payouts",
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
            'api-key: '.config('services.fincra.secret'),
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
                // return $this->resolveTransaction(json_decode($response));
            }
            // return $this->changePaymentToPending($ref);
            $data = json_decode($response, true);

            if($data['message'] == "no Route matched with those values"){
                throw new \Exception("No response from Fincra");
            }
        }

    }
}
