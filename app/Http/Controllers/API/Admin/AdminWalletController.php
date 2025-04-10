<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
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
        return $this->adminWalletService->getTransactions();
    }
}
