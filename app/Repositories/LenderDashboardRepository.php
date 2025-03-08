<?php

namespace App\Repositories;

use App\Models\Business;
use App\Models\CreditLendersWallet;
use App\Models\CreditLenderTxnHistory;
use Illuminate\Http\Request;

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

        //create a pending transaction
        $transaction = CreditLenderTxnHistory::create([
            'lender_id' => $business_id,
            'amount' => $request->amount,
            'status' => 'initiated',
            'type' => 'deposit',
            'description' => 'Deposit to wallet',
            'payment_method' => 'fincra'
        ]);

        return $transaction;

    }

    public function completeWalletDeposit($data)
    {
        $body = $data->data;
        $merchantReference = $body->merchantReference;

        $transaction = CreditLenderTxnHistory::where('identifier', $merchantReference)->first();

        if ($transaction) {
            $transaction->status = 'success';
            $transaction->reference = $body->reference;
            $transaction->meta = json_encode($body);
            $transaction->save();

            //update the wallet balance
            $wallet = CreditLendersWallet::where('lender_id', $transaction->lender_id)->where('type', 'deposit')->first();
            $wallet->prev_balance = $wallet->current_balance;
            $wallet->current_balance += $transaction->amount;
            $wallet->last_transaction_ref = $body->reference;
            $wallet->save();
        }

        return $transaction;
    }
}
