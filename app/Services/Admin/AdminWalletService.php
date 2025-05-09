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

    public function getTransactions():\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->adminWalletRepository->getTransactions();
    }

    public function getAdminTransactions():\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->adminWalletRepository->getAdminTransactions();
    }

    public function getPayOutTransactions():\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->adminWalletRepository->getPayOutTransactions();
    }

    public function getWalletUserStats($businessId)
    {
        return $this->adminWalletRepository->getWalletUserStats($businessId);
    }
}
