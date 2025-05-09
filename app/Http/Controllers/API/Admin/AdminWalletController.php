<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditTransactionsResource;
use App\Http\Resources\TenmgTransactionResource;
use App\Services\Admin\AdminWalletService;
use Illuminate\Http\Request;

class AdminWalletController extends Controller
{
    public function __construct(private AdminWalletService $adminWalletService)
    {

    }

    public function getWalletStats()
    {
        return $this->adminWalletService->getWalletStats();
    }

    public function getTransactions(Request $request)
    {
        $transactions = $this->adminWalletService->getTransactions();

        return $this->returnJsonResponse(
            data: CreditTransactionsResource::collection($transactions)->response()->getData(true)
        );
    }

    public function getAdminTransactions(Request $request)
    {
        $transactions = $this->adminWalletService->getAdminTransactions();

        return $this->returnJsonResponse(
            data: TenmgTransactionResource::collection($transactions)->response()->getData(true)
        );
    }

    public function getPayOutTransactions(Request $request)
    {
        $transactions = $this->adminWalletService->getPayOutTransactions();

        return $this->returnJsonResponse(
            data: CreditTransactionsResource::collection($transactions)->response()->getData(true)
        );
    }

    public function getWalletUserStats($businessId)
    {
        $response = $this->adminWalletService->getWalletUserStats($businessId);

        return $this->returnJsonResponse(
            data: $response
        );
    }
}
