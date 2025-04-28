<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditTransactionsResource;
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
}
