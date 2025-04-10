<?php

namespace App\Services\Admin;

use App\Repositories\AdminWalletRepository;

class AdminWalletService
{
    public function __construct(private AdminWalletRepository $adminWalletRepository)
    {
    }

    public function getWalletStats()
    {
        return $this->adminWalletRepository->getWalletStats();
    }

    public function getTransactions()
    {
        return $this->adminWalletRepository->getTransactions();
    }
}
