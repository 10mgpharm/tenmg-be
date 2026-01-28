<?php

declare(strict_types=1);

namespace App\Webhooks\Fincra\Handlers;

use App\Models\Administration\ServiceProvider;
use App\Models\Business\Business;
use App\Models\Transaction\Deposit;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\VirtualAccount;
use App\Models\User;
use App\Notifications\DepositFailed;
use App\Notifications\DepositSuccessful;
use App\Services\Payout\Providers\FincraProvider;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class CollectionWebhookJob extends ProcessWebhookJob
{
    protected FincraProvider $fincraProvider;

    protected WalletService $walletService;

    public function __construct($webhookCall)
    {
        parent::__construct($webhookCall);
        $this->fincraProvider = app(FincraProvider::class);
        $this->walletService = app(WalletService::class);
    }

    public function handle(): void
    {
        $payload = $this->webhookCall->payload;
        $event = isset($payload['event']) ? $payload['event'] : null;
        $data = isset($payload['data']) ? $payload['data'] : [];

        // Save payload to file for debugging/sharing
        $this->savePayloadToFile($payload, $event);

        if (! $event) {
            Log::error('Fincra webhook received with no event type', [
                'webhook_id' => $this->webhookCall->id,
            ]);

            return;
        }

        switch ($event) {
            case 'collection.successful':
                $this->handleSuccessfulCollection($data);
                break;

            case 'collection.failed':
                $this->handleFailedCollection($data);
                break;

            default:
                Log::info('Unhandled Fincra collection event: '.$event);
        }
    }

    protected function handleSuccessfulCollection(array $data): void
    {
        // ALWAYS verify with provider first - never trust webhook data alone
        $verificationResult = $this->fincraProvider->verifyDeposit($data['reference']);

        if (! $verificationResult['success']) {
            Log::error('Fincra deposit verification failed - ignoring webhook', [
                'reference' => $data['reference'],
                'webhook_data' => $data,
            ]);

            return;
        }

        $verifiedData = $verificationResult['data'];

        // Only process if provider confirms success - ignore webhook status
        if ($verifiedData['status'] !== 'successful') {
            Log::warning('Webhook claims success but provider verification shows different status - ignoring', [
                'reference' => $data['reference'],
                'webhook_status' => $data['status'] ?? 'unknown',
                'provider_status' => $verifiedData['status'],
                'webhook_data' => $data,
            ]);

            return;
        }

        $status = 'successful';

        $virtualAccount = VirtualAccount::where('account_number', $data['recipientAccountNumber'])
            ->orWhere('provider_reference', $data['virtualAccount'])
            ->first();

        if (! $virtualAccount) {
            Log::error('Fincra webhook: Virtual account not found', [
                'account_number' => $data['recipientAccountNumber'],
            ]);

            return;
        }

        $wallet = $virtualAccount->wallet;

        if (! $wallet) {
            Log::error('Fincra webhook: Wallet not found for virtual account', [
                'virtual_account_id' => $virtualAccount->id,
            ]);

            return;
        }

        // Ensure currency info is loaded
        if (! $wallet->relationLoaded('currencyInfo')) {
            $wallet->load('currencyInfo');
        }

        // Validate that the webhook currency matches the wallet currency
        $webhookCurrency = strtolower($data['destinationCurrency'] ?? '');
        $walletCurrency = strtolower($wallet->currencyInfo->code ?? '');

        if ($webhookCurrency !== $walletCurrency) {
            Log::error('Fincra webhook: Currency mismatch', [
                'webhook_currency' => $webhookCurrency,
                'wallet_currency' => $walletCurrency,
                'wallet_id' => $wallet->id,
            ]);

            return;
        }

        // Determine the wallet owner entity
        $entityId = null;
        $entityType = null;

        if ($wallet->customer_id) {
            $entityId = $wallet->customer_id;
            $entityType = User::class;
        } elseif ($wallet->business_id) {
            $entityId = $wallet->business_id;
            $entityType = Business::class;
        } else {
            Log::error('Fincra webhook: Wallet has no owner entity');

            return;
        }

        try {
            DB::beginTransaction();

            $serviceProvider = ServiceProvider::where('slug', 'fincra')->first();

            if (! $serviceProvider) {
                Log::error('Fincra service provider not found');

                return;
            }

            // Create or fetch existing deposit by provider reference (idempotency)
            $deposit = Deposit::firstOrCreate(
                ['processor_reference' => $data['reference']],
                [
                    'reference' => 'DEP-'.strtoupper(Str::random(8)).'-'.date('YmdHis'),
                    'entity_id' => $entityId,
                    'entity_type' => $entityType,
                    'entity_label' => $entityType === User::class ? 'customer' : 'business',
                    'currency_id' => $wallet->currencyInfo->id,
                    'wallet_id' => $wallet->id,
                    'deposit_method' => 'virtual_account',
                    'amount' => $data['destinationAmount'],
                    'fee' => $data['fee'] ?? 0,
                    'processor' => $serviceProvider->id,
                    'processor_status' => $data['status'] ?? 'pending',
                    'payer_name' => $data['senderAccountName'],
                    'payer_account_number' => $data['senderAccountNumber'],
                    'payer_bank_name' => $data['senderBankName'],
                    'payer_bank_code' => $data['senderBankDetails']['bankCode'] ?? null,
                    'payer_narration' => $data['description'],
                    'status' => $status === 'successful' ? 'successful' : 'pending',
                    'is_transaction_logged' => false,
                ]
            );

            // If already logged, bail out (idempotent)
            if ($deposit->is_transaction_logged) {
                DB::commit();
                Log::info('Fincra webhook: Deposit already logged, skipping', [
                    'reference' => $deposit->reference,
                    'processor_reference' => $data['reference'],
                ]);

                return;
            }

            // Ensure we don't create duplicate transactions for the same deposit
            $existingTransaction = Transaction::where('transaction_reference', $deposit->reference)->first();
            $transaction = $existingTransaction ?: Transaction::create([
                'id' => Str::uuid(),
                'entity_id' => $entityId,
                'entity_type' => $entityType,
                'entity_label' => $entityType === User::class ? 'customer' : 'business',
                'wallet_id' => $wallet->id,
                'currency_id' => $wallet->currencyInfo->id,
                'transaction_category' => 'credit',
                'transaction_type' => 'deposit',
                'transaction_method' => 'virtual_account',
                'transaction_reference' => $deposit->reference,
                'transaction_narration' => 'Deposit via '.($data['senderBankName'] ?? 'bank transfer'),
                'amount' => $data['destinationAmount'],
                'processor' => $serviceProvider->id,
                'processor_reference' => $data['reference'],
                'status' => $status === 'successful' ? 'successful' : 'pending',
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance + $data['destinationAmount'],
            ]);

            if ($status === 'successful' && ! $deposit->is_transaction_logged) {
                $this->walletService->creditWallet(
                    wallet: $wallet,
                    amount: (float) $data['destinationAmount'],
                    transactionReference: $deposit->reference,
                    transactionId: $transaction->id
                );

                // Send successful deposit notification
                try {
                    $this->sendDepositNotification($entityId, $entityType, $deposit, 'successful');
                } catch (\Exception $e) {
                    Log::error('Error sending deposit notification: '.$e->getMessage(), [
                        'deposit_id' => $deposit->id,
                        'exception' => $e,
                    ]);
                    // Don't rethrow - we don't want notification failures to affect the transaction
                }
            }

            $deposit->update(['is_transaction_logged' => true]);

            DB::commit();

            Log::info('Fincra deposit processed', [
                'deposit_reference' => $deposit->reference,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing Fincra deposit: '.$e->getMessage());
        }
    }

    protected function handleFailedCollection(array $data): void
    {
        // ALWAYS verify with provider first - never trust webhook data alone
        $verificationResult = $this->fincraProvider->verifyDeposit($data['reference']);

        if (! $verificationResult['success']) {
            Log::error('Fincra deposit verification failed - ignoring webhook', [
                'reference' => $data['reference'],
                'webhook_data' => $data,
            ]);

            return;
        }

        $verifiedData = $verificationResult['data'];

        // Only process if provider confirms failure - ignore webhook status
        if ($verifiedData['status'] !== 'failed') {
            Log::warning('Webhook claims failure but provider verification shows different status - ignoring', [
                'reference' => $data['reference'],
                'webhook_status' => $data['status'] ?? 'unknown',
                'provider_status' => $verifiedData['status'],
                'webhook_data' => $data,
            ]);

            return;
        }

        // Check if this deposit has already been processed - do this early
        $existingDeposit = Deposit::where('processor_reference', $data['reference'])->first();
        if ($existingDeposit) {
            Log::info('Fincra webhook: Failed deposit already processed', [
                'reference' => $data['reference'],
                'deposit_id' => $existingDeposit->id,
            ]);

            return;
        }

        $virtualAccount = VirtualAccount::where('account_number', $data['recipientAccountNumber'])
            ->orWhere('provider_reference', $data['virtualAccount'])
            ->first();

        if (! $virtualAccount) {
            Log::error('Fincra webhook: Virtual account not found for failed collection');

            return;
        }

        $wallet = $virtualAccount->wallet;

        if (! $wallet) {
            Log::error('Fincra webhook: Wallet not found for virtual account');

            return;
        }

        // Ensure currency info is loaded
        if (! $wallet->relationLoaded('currencyInfo')) {
            $wallet->load('currencyInfo');
        }

        // Validate that the webhook currency matches the wallet currency
        $webhookCurrency = strtolower($data['destinationCurrency'] ?? '');
        $walletCurrency = strtolower($wallet->currencyInfo->code ?? '');

        if ($webhookCurrency !== $walletCurrency) {
            Log::error('Fincra webhook: Currency mismatch in failed collection', [
                'webhook_currency' => $webhookCurrency,
                'wallet_currency' => $walletCurrency,
                'wallet_id' => $wallet->id,
            ]);

            return;
        }

        $entityId = null;
        $entityType = null;

        if ($wallet->customer_id) {
            $entityId = $wallet->customer_id;
            $entityType = User::class;
        } elseif ($wallet->business_id) {
            $entityId = $wallet->business_id;
            $entityType = Business::class;
        } else {
            Log::error('Fincra webhook: Wallet has no owner entity');

            return;
        }

        try {
            DB::beginTransaction();

            $serviceProvider = ServiceProvider::where('slug', 'fincra')->first();

            if (! $serviceProvider) {
                Log::error('Fincra service provider not found');
                DB::rollBack();

                return;
            }

            $depositRef = 'DEP-'.strtoupper(Str::random(8)).'-'.date('YmdHis');

            // Add transaction record for failed collection
            Transaction::create([
                'id' => Str::uuid(),
                'entity_id' => $entityId,
                'entity_type' => $entityType,
                'entity_label' => $entityType === User::class ? 'customer' : 'business',
                'wallet_id' => $wallet->id,
                'currency_id' => $wallet->currencyInfo->id,
                'transaction_category' => 'credit',
                'transaction_type' => 'deposit',
                'transaction_method' => 'virtual_account',
                'transaction_reference' => $depositRef,
                'transaction_narration' => 'Failed deposit via '.($data['senderBankName'] ?? 'bank transfer'),
                'amount' => $data['destinationAmount'] ?? 0,
                'processor' => $serviceProvider->id,
                'processor_reference' => $data['reference'],
                'status' => 'failed',
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance,
            ]);

            $deposit = Deposit::create([
                'reference' => $depositRef,
                'entity_id' => $entityId,
                'entity_type' => $entityType,
                'entity_label' => $entityType === User::class ? 'customer' : 'business',
                'currency_id' => $wallet->currencyInfo->id,
                'wallet_id' => $wallet->id,
                'deposit_method' => 'virtual_account',
                'amount' => $data['destinationAmount'] ?? 0,
                'fee' => $data['fee'] ?? 0,
                'processor' => $serviceProvider->id,
                'processor_reference' => $data['reference'],
                'processor_status' => $data['status'] ?? 'failed',
                'payer_name' => $data['senderAccountName'] ?? null,
                'payer_account_number' => $data['senderAccountNumber'] ?? null,
                'payer_bank_name' => $data['senderBankName'] ?? null,
                'payer_bank_code' => $data['senderBankDetails']['bankCode'] ?? null,
                'payer_narration' => $data['description'] ?? null,
                'status' => 'failed',
                'is_transaction_logged' => true,
            ]);

            DB::commit();

            // Send failed deposit notification
            try {
                $this->sendDepositNotification($entityId, $entityType, $deposit, 'failed');
            } catch (\Exception $e) {
                Log::error('Error sending deposit failure notification: '.$e->getMessage(), [
                    'deposit_id' => $deposit->id,
                    'exception' => $e,
                ]);
                // Don't rethrow - we don't want notification failures to affect the transaction
            }

            Log::info('Failed Fincra deposit recorded', [
                'deposit_reference' => $depositRef,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error recording failed Fincra deposit: '.$e->getMessage());
        }
    }

    /**
     * Send notification to the entity (customer or business)
     */
    protected function sendDepositNotification(string $entityId, string $entityType, Deposit $deposit, string $status): void
    {
        if ($entityType === User::class) {
            $customer = User::find($entityId);
            if ($customer) {
                if ($status === 'successful') {
                    $customer->notify(new DepositSuccessful($deposit));
                } else {
                    $customer->notify(new DepositFailed($deposit));
                }
            }
        } elseif ($entityType === Business::class) {
            $business = Business::find($entityId);
            if ($business) {
                if ($status === 'successful') {
                    $business->notify(new DepositSuccessful($deposit));
                } else {
                    $business->notify(new DepositFailed($deposit));
                }
            }
        }
    }

    /**
     * Save webhook payload to file for debugging/sharing
     */
    private function savePayloadToFile(array $payload, ?string $event): void
    {
        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $eventType = $event ?: 'unknown';
            $webhookId = $this->webhookCall->id;

            $filename = "fincra_collection_webhook_{$eventType}_{$timestamp}_{$webhookId}.json";
            $filepath = storage_path("app/webhook_payloads/{$filename}");

            // Ensure directory exists
            $directory = dirname($filepath);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Save payload as pretty JSON
            file_put_contents($filepath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            Log::info('Fincra collection webhook payload saved to file', [
                'filepath' => $filepath,
                'event' => $event,
                'webhook_id' => $webhookId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save Fincra collection webhook payload to file', [
                'error' => $e->getMessage(),
                'webhook_id' => $this->webhookCall->id,
            ]);
        }
    }
}
