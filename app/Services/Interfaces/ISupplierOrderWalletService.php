<?php

namespace App\Services\Interfaces;

use App\Models\Business;
use App\Models\EcommerceOrder;
use Illuminate\Support\Collection;

/**
 * Interface IWalletService defines the contract for handling wallet operations,
 * such as crediting funds, tracking transactions, and resolving balances for suppliers.
 */
interface ISupplierOrderWalletService
{
    /**
     * Set the business(s) for whom the wallet operations will be performed.
     *
     * @param  User  $user  The user instance.
     */
    public function forBusiness(Business|Collection $business): self;


    /**
     * Handle crediting the supplier(s) wallet based on a completed order.
     * This should also record the transaction in the ecommerce_transactions table.
     *
     * @param EcommerceOrder $order
     * @return $this
     */
    public function credit(EcommerceOrder $order): self;

    /**
     * Handle debiting the supplier(s) wallet based on a cancelled order.
     * This should also record the transaction in the ecommerce_transactions table.
     *
     * @param EcommerceOrder $order
     * @return $this
     */
    public function debit(EcommerceOrder $order): self;
}
