<?php

namespace App\Services\Vendor;

use App\Repositories\VendorWalletRepository;

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
}
