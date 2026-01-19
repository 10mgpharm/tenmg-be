<?php

namespace App\Services\Interfaces;

use App\Models\Business;
use App\Models\LenderKycSession;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface IVirtualAccountService defines the contract for virtual account operations
 */
interface IVirtualAccountService
{
    /**
     * Create a virtual account for a wallet
     * Uses provider configured on currency, KYC session optional for providers that require it
     */
    public function createVirtualAccount(
        Wallet $wallet,
        Business $business,
        ?LenderKycSession $kycSession = null
    ): ?VirtualAccount;

    /**
     * Get a virtual account by ID
     */
    public function getVirtualAccount(string $virtualAccountId): ?VirtualAccount;

    /**
     * List virtual accounts with optional filters
     */
    public function listVirtualAccounts(?Business $business = null, ?Wallet $wallet = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator|Collection;

    /**
     * Update virtual account status
     */
    public function updateVirtualAccountStatus(VirtualAccount $virtualAccount, string $status): VirtualAccount;
}
