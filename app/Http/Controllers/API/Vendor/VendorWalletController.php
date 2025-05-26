<?php

namespace App\Http\Controllers\API\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditTransactionHistoryResource;
use App\Services\Vendor\VendorWalletService;
use Illuminate\Http\Request;

class VendorWalletController extends Controller
{
    public function __construct(private VendorWalletService $vendorWalletService)
    {

    }

    public function getWalletStats()
    {
        return $this->vendorWalletService->getWalletStats();
    }

    public function getTransactions()
    {
        $transactionList = $this->vendorWalletService->getTransactions();

        return $this->returnJsonResponse(
            data: CreditTransactionHistoryResource::collection($transactionList)->response()->getData(true)
        );
    }

    public function initWithdrawals(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'bankAccountId' => 'required|exists:ecommerce_bank_accounts,id',
        ]);

        $withdrawal = $this->vendorWalletService->initWithdrawals($request);

        return $this->returnJsonResponse(
            message: 'Withdrawal initialized successfully. An otp has been sent to your registered email',
            data: $withdrawal
        );
    }

    public function withdrawFunds(Request $request)
    {
        $request->validate([
            'reference' => 'required|exists:credit_transaction_histories,identifier',
            'otp' => 'required|exists:otps,code',
        ]);

        $response = $this->vendorWalletService->withdrawFunds($request);

        return $this->returnJsonResponse(
            message: 'Withdrawal request submitted successfully.',
            data: $response
        );

    }


}
