<?php

namespace App\Services\Vendor;

use App\Repositories\VendorWalletRepository;
use Illuminate\Http\Request;

class VendorWalletService
{
    public function __construct(private VendorWalletRepository $vendorWalletRepository)
    {
    }

    public function getWalletStats()
    {
        return $this->vendorWalletRepository->getWalletStats();
    }

    public function getTransactions()
    {
        return $this->vendorWalletRepository->getTransactions();
    }

    public function initWithdrawals(Request $request)
    {
        return $this->vendorWalletRepository->initWithdrawals($request);
    }

    public function withdrawFunds(Request $request)
    {
        return $this->vendorWalletRepository->withdrawFunds($request);
    }
}
