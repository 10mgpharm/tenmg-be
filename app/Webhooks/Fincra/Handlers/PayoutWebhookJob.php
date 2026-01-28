<?php

declare(strict_types=1);

namespace App\Webhooks\Fincra\Handlers;

use App\Models\Transaction\Transaction;
use App\Models\Transaction\Wallet;
use App\Models\Transaction\Withdrawal;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class PayoutWebhookJob extends ProcessWebhookJob
{
    protected WalletService $walletService;

    public function __construct($webhookCall)
    {
        parent::__construct($webhookCall);
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

        Log::info('Processing Fincra webhook', [
            'event' => $event,
            'webhook_id' => $this->webhookCall->id,
            'reference' => $data['reference'] ?? 'unknown',
        ]);

        switch ($event) {
            case 'payout.successful':
                $this->handleSuccessfulPayout($data);
                break;

            case 'payout.failed':
                $this->handleFailedPayout($data);
                break;

            case 'payout.reversed':
                $this->handleReversedPayout($data);
                break;

            default:
                Log::info('Unhandled Fincra payout event: '.$event, [
                    'webhook_id' => $this->webhookCall->id,
                ]);
        }
    }

    /**
     * Handle a successful payout webhook
     */
    protected function handleSuccessfulPayout(array $data): void
    {
        $reference = $data['reference'] ?? null;
        $customerReference = $data['customerReference'] ?? null;

        if (! $reference && ! $customerReference) {
            Log::error('Fincra webhook: Missing reference and customerReference', [
                'webhook_id' => $this->webhookCall->id,
                'data' => $data,
            ]);

            return;
        }

        // ALWAYS verify with provider first - never trust webhook data alone
        $withdrawalService = app(WithdrawalService::class);
        $fincraProvider = app(\App\Services\Payout\Providers\FincraProvider::class);

        // Create dummy transaction for status check
        $dummyTransaction = new \App\Models\Transaction\Transaction([
            'transaction_reference' => $customerReference ?: $reference,
            'processor_reference' => $reference,
            'status' => 'pending',
        ]);

        $verificationResult = $fincraProvider->checkTransactionStatus($dummyTransaction);

        if (! $verificationResult['success']) {
            Log::error('Fincra payout verification failed - ignoring webhook', [
                'reference' => $reference,
                'customer_reference' => $customerReference,
                'webhook_data' => $data,
            ]);

            return;
        }

        // Only process if provider confirms success - ignore webhook status
        if ($verificationResult['status'] !== 'successful') {
            Log::warning('Webhook claims success but provider verification shows different status - ignoring', [
                'reference' => $reference,
                'customer_reference' => $customerReference,
                'webhook_status' => $data['status'] ?? 'unknown',
                'provider_status' => $verificationResult['status'],
                'webhook_data' => $data,
            ]);

            return;
        }

        // Use the WithdrawalService to handle the successful payout
        $result = $withdrawalService->handleSuccessfulPayout($data);

        if ($result) {
            Log::info('Successfully processed payout.successful webhook', [
                'reference' => $reference,
                'customer_reference' => $customerReference,
            ]);
        } else {
            Log::warning('Failed to process payout.successful webhook', [
                'reference' => $reference,
                'customer_reference' => $customerReference,
                'data' => $data,
            ]);
        }
    }

    /**
     * Handle a failed payout webhook
     */
    protected function handleFailedPayout(array $data): void
    {
        $reference = $data['reference'] ?? null;
        $customerReference = $data['customerReference'] ?? null;

        if (! $reference && ! $customerReference) {
            Log::error('Fincra webhook: Missing reference and customerReference', [
                'webhook_id' => $this->webhookCall->id,
                'data' => $data,
            ]);

            return;
        }

        // ALWAYS verify with provider first - never trust webhook data alone
        $withdrawalService = app(WithdrawalService::class);
        $fincraProvider = app(\App\Services\Payout\Providers\FincraProvider::class);

        // Create dummy transaction for status check
        $dummyTransaction = new \App\Models\Transaction\Transaction([
            'transaction_reference' => $customerReference ?: $reference,
            'processor_reference' => $reference,
            'status' => 'pending',
        ]);

        $verificationResult = $fincraProvider->checkTransactionStatus($dummyTransaction);

        if (! $verificationResult['success']) {
            Log::error('Fincra payout verification failed - ignoring webhook', [
                'reference' => $reference,
                'customer_reference' => $customerReference,
                'webhook_data' => $data,
            ]);

            return;
        }

        // Only process if provider confirms failure - ignore webhook status
        if ($verificationResult['status'] !== 'failed') {
            Log::warning('Webhook claims failure but provider verification shows different status - ignoring', [
                'reference' => $reference,
                'customer_reference' => $customerReference,
                'webhook_status' => $data['status'] ?? 'unknown',
                'provider_status' => $verificationResult['status'],
                'webhook_data' => $data,
            ]);

            return;
        }

        // Use the WithdrawalService to handle the failed payout
        $result = $withdrawalService->handleFailedPayout($data);

        if ($result) {
            Log::info('Successfully processed payout.failed webhook', [
                'reference' => $reference,
                'customer_reference' => $customerReference,
            ]);
        } else {
            Log::warning('Failed to process payout.failed webhook', [
                'reference' => $reference,
                'customer_reference' => $customerReference,
                'data' => $data,
            ]);
        }
    }

    /**
     * Handle a reversed payout webhook
     */
    protected function handleReversedPayout(array $data): void
    {
        $reference = $data['reference'] ?? null;
        $customerReference = $data['customerReference'] ?? null;

        if (! $reference && ! $customerReference) {
            Log::error('Fincra webhook: Missing reference and customerReference', [
                'webhook_id' => $this->webhookCall->id,
                'data' => $data,
            ]);

            return;
        }

        // ALWAYS verify with provider first - never trust webhook data alone
        $fincraProvider = app(\App\Services\Payout\Providers\FincraProvider::class);

        // Create dummy transaction for status check
        $dummyTransaction = new \App\Models\Transaction\Transaction([
            'transaction_reference' => $customerReference ?: $reference,
            'processor_reference' => $reference,
            'status' => 'pending',
        ]);

        $verificationResult = $fincraProvider->checkTransactionStatus($dummyTransaction);

        if (! $verificationResult['success']) {
            Log::error('Fincra payout verification failed - ignoring webhook', [
                'reference' => $reference,
                'customer_reference' => $customerReference,
                'webhook_data' => $data,
            ]);

            return;
        }

        // Only process if provider confirms reversal - ignore webhook status
        if ($verificationResult['status'] !== 'reversed') {
            Log::warning('Webhook claims reversal but provider verification shows different status - ignoring', [
                'reference' => $reference,
                'customer_reference' => $customerReference,
                'webhook_status' => $data['status'] ?? 'unknown',
                'provider_status' => $verificationResult['status'],
                'webhook_data' => $data,
            ]);

            return;
        }

        // Try to find the withdrawal by provider reference or our reference
        $withdrawal = null;
        if ($reference) {
            $withdrawal = Withdrawal::where('processor_reference', $reference)->first();
        }

        if (! $withdrawal && $customerReference) {
            $withdrawal = Withdrawal::where('reference', $customerReference)->first();
        }

        if (! $withdrawal) {
            Log::error('Could not find withdrawal for reversed payout webhook', [
                'reference' => $reference,
                'customer_reference' => $customerReference,
            ]);

            return;
        }

        // Mark the withdrawal as reversed and the transaction as reversed if it exists
        $withdrawal->status = 'reversed';
        $withdrawal->processor_status = 'reversed';
        $withdrawal->metadata = array_merge((array) $withdrawal->metadata, [
            'reversed_data' => $data,
            'reversed_at' => now()->toDateTimeString(),
        ]);
        $withdrawal->save();

        // If there's a transaction, reverse it
        if ($withdrawal->is_transaction_logged) {
            $transaction = Transaction::where('transaction_reference', $withdrawal->reference)
                ->where('transaction_type', 'withdrawal')
                ->first();

            if ($transaction) {
                // Create a reversal transaction
                $wallet = Wallet::find($transaction->wallet_id);
                $amount = abs($transaction->amount); // Make it positive for credit

                $reversalTransaction = Transaction::create([
                    'entity_id' => $transaction->entity_id,
                    'entity_type' => $withdrawal->entity_type, // ✅ Use withdrawal's entity_type (already correct)
                    'entity_label' => $transaction->entity_label,
                    'wallet_id' => $transaction->wallet_id,
                    'currency_id' => $transaction->currency_id, // ✅ Also fix currency field
                    'transaction_category' => 'credit',
                    'transaction_type' => 'reversal',
                    'transaction_method' => 'bank_transfer',
                    'transaction_reference' => 'REV-'.$withdrawal->reference,
                    'transaction_narration' => 'Reversal for withdrawal '.$withdrawal->reference,
                    'amount' => $amount,
                    'processor' => $withdrawal->processor,
                    'processor_reference' => $reference,
                    'status' => 'successful',
                    'balance_before' => $wallet->balance - $amount,
                    'balance_after' => $wallet->balance,
                ]);

                $this->walletService->creditWallet(
                    wallet: $wallet,
                    amount: $amount,
                    transactionReference: 'REV-'.$withdrawal->reference,
                    transactionId: $reversalTransaction->id
                );
            }
        }

        Log::info('Successfully processed payout.reversed webhook', [
            'reference' => $reference,
            'customer_reference' => $customerReference,
            'withdrawal_id' => $withdrawal->id,
        ]);
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

            $filename = "fincra_payout_webhook_{$eventType}_{$timestamp}_{$webhookId}.json";
            $filepath = storage_path("app/webhook_payloads/{$filename}");

            // Ensure directory exists
            $directory = dirname($filepath);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Save payload as pretty JSON
            file_put_contents($filepath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            Log::info('Fincra webhook payload saved to file', [
                'filepath' => $filepath,
                'event' => $event,
                'webhook_id' => $webhookId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save Fincra webhook payload to file', [
                'error' => $e->getMessage(),
                'webhook_id' => $this->webhookCall->id,
            ]);
        }
    }
}
