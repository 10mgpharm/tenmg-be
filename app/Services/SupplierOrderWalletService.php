<?php

namespace App\Services;

use App\Models\Business;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderDetail;
use App\Models\EcommerceTransaction;
use App\Models\EcommerceWallet;
use App\Services\Interfaces\ISupplierOrderWalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Constants\EcommerceWalletConstants;
use App\Models\TenMgWallet;

/**
 * SupplierOrderWalletService handles credit and debit operations for supplier wallets
 * based on the outcome of ecommerce orders.
 */
class SupplierOrderWalletService implements ISupplierOrderWalletService
{
    /**
     * The business(es) for whom the wallet operations are being performed.
     *
     * @var Collection<int, Business>|null
     */
    protected Business|Collection|null $businesses = null;

    /**
     * The order associated with the wallet operation.
     *
     * @var EcommerceOrder|null
     */
    protected EcommerceOrder|null $order = null;

    /**
     * Construct the service.
     *
     * @param bool $shouldFail Whether to throw exceptions on failure or just log them.
     */
    public function __construct(protected bool $shouldFail = false) {}

    /**
     * Set the business or businesses for whom the wallet operations will be performed.
     *
     * @param Business|Collection $business One or more business models.
     * @return $this
     */
    public function forBusiness(Business|Collection $business): self
    {
        $this->businesses = $business instanceof Collection ? $business : collect([$business]);
        return $this;
    }

    /**
     * Credit the supplier's wallet(s) based on a completed order.
     * A credit transaction is recorded for each supplier involved.
     *
     * @param EcommerceOrder $order The ecommerce order that was completed.
     * @return $this
     *
     * @throws BadRequestHttpException
     */
    public function credit(EcommerceOrder $order): self
    {
        $this->order = $order;

        // Only proceed if order is completed
        if ($order->status !== 'COMPLETED') {
            $this->failOrLog('The order must be completed to perform the credit.');
            return $this;
        }

        try {
            DB::transaction(function () use ($order) {
                $payouts = $this->payouts($order);

                // Ensure there are payouts to process
                if ($payouts->isEmpty()) {
                    $this->failOrLog('No payouts found for the provided order and suppliers.');
                    return;
                }

                foreach ($payouts as $row) {
                    $last_transaction = $this->last($row->supplier_id, $order->id);

                    // Prevent double-crediting for supplier this also prevents double-crediting for tenmg
                    if ($last_transaction && $last_transaction->txn_type === EcommerceWalletConstants::TXN_TYPE_CREDIT) {
                        $name = $row->supplier->name ?? 'Unknown';
                        $this->failOrLog("Can't credit supplier {$name} ({$row->supplier_id}) twice for this order.");
                        continue;
                    }

                    $supplier_wallet = $this->lockOrCreateWalletForSupplier($row->supplier_id);

                    $supplier_wallet->previous_balance = $supplier_wallet->current_balance;
                    $supplier_wallet->current_balance += $row->payout;
                    $supplier_wallet->save();

                    // Record supplier credit transaction
                    $supplier_wallet->transactions()->create([
                        'supplier_id' => $supplier_wallet->business_id,
                        'ecommerce_order_id' => $order->id,
                        'txn_type' => EcommerceWalletConstants::TXN_TYPE_CREDIT,
                        'txn_group' => EcommerceWalletConstants::SUPPLIER_TXN_GROUP_ORDER_PAYMENT,
                        'amount' => $row->payout,
                        'balance_before' => $supplier_wallet->previous_balance,
                        'balance_after' => $supplier_wallet->current_balance,
                        'status' => 'CREDIT',
                    ]);

                    // lock or create the tenmg's wallet for commission processing and update current and previous balance
                    $tenmg_wallet = $this->lockOrCreateWalletForTenMg();

                    $tenmg_wallet->previous_balance = $tenmg_wallet->current_balance;
                    $tenmg_wallet->current_balance += $row->tenmg_commission;;
                    $tenmg_wallet->save();

                    // Record tenmg commission credit transaction
                    $tenmg_wallet->ecommerceTransactions()->create([
                        'ecommerce_order_id' => $order->id,
                        'txn_type' => EcommerceWalletConstants::TXN_TYPE_CREDIT,
                        'txn_group' => EcommerceWalletConstants::TENMG_TXN_GROUP_ORDER_PAYMENT,
                        'amount' => $row->tenmg_commission,
                        'balance_before' => $tenmg_wallet->previous_balance,
                        'balance_after' => $tenmg_wallet->current_balance,
                        'status' => 'CREDIT',
                    ]);
                }
            });
        } catch (\Throwable $th) {
            Log::error('Failed to perform the supplier credit operation: ' . $th->getMessage());

            if ($this->shouldFail) {
                throw new BadRequestHttpException('Failed to perform the supplier credit operation.');
            }
        }

        return $this;
    }

    /**
     * Debit the supplier's wallet(s) based on a cancelled order.
     * A debit transaction is recorded for each supplier involved.
     *
     * @param EcommerceOrder $order The ecommerce order that was cancelled.
     * @return $this
     *
     * @throws BadRequestHttpException
     */
    public function debit(EcommerceOrder $order): self
    {
        // Only proceed if order is canceled
        if ($order->status !== 'CANCELED') {
            $this->failOrLog('The order must be canceled to perform the debit.');
            return $this;
        }

        DB::transaction(function () use ($order) {
            $payouts = $this->payouts($order);

            // Ensure there are payouts to reverse
            if ($payouts->isEmpty()) {
                $this->failOrLog('No payouts found for the provided order and suppliers.');
                return;
            }

            foreach ($payouts as $row) {
                $last_transaction = $this->last($row->supplier_id, $order->id);

                // Prevent double-debiting
                if ($last_transaction && $last_transaction->txn_type === EcommerceWalletConstants::TXN_TYPE_DEBIT) {
                    $name = $row->supplier->name ?? 'Unknown';
                    $this->failOrLog("Can't debit supplier {$name} ({$row->supplier_id}) twice for this order.");
                    continue;
                } else if(!$last_transaction) {
                    $name = $row->supplier->name ?? 'Unknown';
                    $this->failOrLog("Cannot debit supplier {$name} ({$row->supplier_id}) for an order ({$order->id}) that was never credited to their wallet. Please ensure the order was successfully credited before performing a debit.");

                    continue;
                } 

                // lock or create the supplier's wallet and update current and previous balance
                $supplier_wallet = $this->lockOrCreateWalletForSupplier($row->supplier_id);

                $supplier_wallet->previous_balance = $supplier_wallet->current_balance;
                $supplier_wallet->current_balance -= $row->payout;
                $supplier_wallet->save();

                // Record supplier debit transaction
                $supplier_wallet->transactions()->create([
                    'supplier_id' => $supplier_wallet->business_id,
                    'ecommerce_order_id' => $order->id,
                    'txn_type' => EcommerceWalletConstants::TXN_TYPE_DEBIT,
                    'txn_group' => EcommerceWalletConstants::SUPPLIER_TXN_GROUP_ORDER_CANCELLATION,
                    'amount' => $row->payout,
                    'balance_before' => $supplier_wallet->previous_balance,
                    'balance_after' => $supplier_wallet->current_balance,
                    'status' => 'DEBIT',
                ]);


                // lock or create the tenmg's wallet for commission processing and update current and previous balance
                $tenmg_wallet = $this->lockOrCreateWalletForTenMg();

                $tenmg_wallet->previous_balance = $tenmg_wallet->current_balance;
                $tenmg_wallet->current_balance -= $row->tenmg_commission;;
                $tenmg_wallet->save();


                // Record tenmg commission debit transaction
                $tenmg_wallet->ecommerceTransactions()->create([
                    'ecommerce_order_id' => $order->id,
                    'txn_type' => EcommerceWalletConstants::TXN_TYPE_DEBIT,
                    'txn_group' => EcommerceWalletConstants::TENMG_TXN_GROUP_ORDER_CANCELLATION,
                    'amount' => $row->tenmg_commission,
                    'balance_before' => $tenmg_wallet->previous_balance,
                    'balance_after' => $tenmg_wallet->current_balance,
                    'status' => 'DEBIT',
                ]);
            }
        });

        return $this;
    }

    /**
     * Check if a transaction for the supplier, order, and type already exists.
     *
     * @param int $supplier_id Supplier's business ID.
     * @param int $order_id Order ID.
     * @param string $type Transaction type: 'CREDIT' or 'DEBIT'.
     * @return bool True if a transaction already exists.
     */
    protected function transactionExists(int $supplier_id, int $order_id, string $type): bool
    {
        return EcommerceTransaction::where([
            'supplier_id' => $supplier_id,
            'ecommerce_order_id' => $order_id,
            'txn_type' => strtoupper($type) === 'DEBIT' ? EcommerceWalletConstants::TXN_TYPE_DEBIT : EcommerceWalletConstants::TXN_TYPE_CREDIT,
            'txn_group' => strtoupper($type) === 'DEBIT' ? EcommerceWalletConstants::SUPPLIER_TXN_GROUP_ORDER_CANCELLATION : EcommerceWalletConstants::SUPPLIER_TXN_GROUP_ORDER_PAYMENT,
        ])->exists();
    }

    /**
     * Get the payouts for each supplier involved in the order.
     *
     * @param EcommerceOrder $order
     * @return Collection A collection of supplier payouts.
     */
    protected function payouts(EcommerceOrder $order): Collection
    {
        return EcommerceOrderDetail::select(
            'supplier_id',
            'tenmg_commission',
            DB::raw('(COALESCE(discount_price, actual_price) * quantity - COALESCE(tenmg_commission, 0)) as payout')
        )
            ->whereHas('order', fn($query) => $query->where('id', $order->id))
            ->when(
                $this->businesses?->count(),
                fn($query) => $query->whereIn('supplier_id', $this->businesses->pluck('id'))
            )
            ->get();
    }

    /**
     * Lock or create a wallet for the given supplier.
     *
     * @param int $supplier_id Supplier's business ID.
     * @return EcommerceWallet The locked or newly created wallet.
     */
    protected function lockOrCreateWalletForSupplier(int $supplier_id): EcommerceWallet
    {
        return EcommerceWallet::where('business_id', $supplier_id)
            ->lockForUpdate()
            ->firstOrCreate(
                ['business_id' => $supplier_id],
                ['previous_balance' => 0, 'current_balance' => 0]
            );
    }

    /**
     * Lock or create a wallet for the tenmg.
     *
     * @return TenMgWallet The locked or newly created wallet.
     */
    protected function lockOrCreateWalletForTenMg(): TenMgWallet
    {
        $wallet = TenMgWallet::lockForUpdate()->latest('id')->first();

        if (!$wallet) {
            $wallet = TenMgWallet::create([
                'previous_balance' => 0,
                'current_balance' => 0,
            ]);
        }
        
        return $wallet;        
    }

    /**
     * Get the last transaction for a supplier and order.
     *
     * @param int $supplier_id Supplier's business ID.
     * @param int $order_id Ecommerce order ID.
     * @return EcommerceTransaction|null The latest transaction or null.
     */
    protected function last(int $supplier_id, int $order_id): ?EcommerceTransaction
    {
        return EcommerceTransaction::where('supplier_id', $supplier_id)
            ->where('ecommerce_order_id', $order_id)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Handles failure behavior based on the shouldFail flag.
     *
     * If shouldFail is true, an exception is thrown with the provided message.
     * Otherwise, the message is logged as an error.
     *
     * @param string $message The message to log or throw.
     * @return void
     *
     * @throws BadRequestHttpException
     */
    protected function failOrLog(string $message): void
    {
        // Throw an exception if the service is set to fail on error; otherwise log the message.
        if ($this->shouldFail) {
            throw new BadRequestHttpException($message);
        }

        Log::error($message);
    }
}
