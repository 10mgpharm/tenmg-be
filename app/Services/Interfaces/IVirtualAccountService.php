<?php

namespace App\Services\Interfaces;

use App\Enums\VirtualAccountType;
use App\Models\Business;
use App\Models\ServiceProvider;
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
     */
    public function createVirtualAccount(Wallet $wallet, ServiceProvider $provider, VirtualAccountType $type, array $providerData = []): VirtualAccount;

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
