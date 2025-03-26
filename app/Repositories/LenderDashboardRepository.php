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
}
