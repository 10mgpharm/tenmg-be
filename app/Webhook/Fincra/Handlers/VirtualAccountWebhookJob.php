<?php

declare(strict_types=1);

namespace App\Webhooks\Fincra\Handlers;

use App\Models\Transaction\VirtualAccount;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class VirtualAccountWebhookJob extends ProcessWebhookJob
{
    public function handle(): void
    {
        $payload = $this->webhookCall->payload;
        $event = isset($payload['event']) ? $payload['event'] : null;
        $data = isset($payload['data']) ? $payload['data'] : [];

        if (! $event) {
            Log::error('Fincra webhook received with no event type', [
                'webhook_id' => $this->webhookCall->id,
            ]);

            return;
        }

        $data = $payload['data'] ?? null;
        if (! $data) {
            Log::error('Fincra webhook received with no data', [
                'webhook_id' => $this->webhookCall->id,
                'event' => $event,
            ]);

            return;
        }

        match ($event) {
            'virtualaccount.approved' => $this->handleVirtualAccountApproved($data),
            'virtualaccount.declined' => $this->handleVirtualAccountDeclined($data),
            'virtualaccount.closed' => $this->handleVirtualAccountClosed($data),
            default => Log::info('Unhandled virtual account event', [
                'webhook_id' => $this->webhookCall->id,
                'event' => $event,
            ])
        };
    }

    protected function handleVirtualAccountApproved(array $data): void
    {
        $virtualAccount = VirtualAccount::where('provider_reference', $data['id'])->first();

        if (! $virtualAccount) {
            Log::error('Virtual account not found for approved event', [
                'provider_reference' => $data['id'],
            ]);

            return;
        }

        $accountInfo = $data['accountInformation'] ?? [];
        $otherInfo = $accountInfo['otherInfo'] ?? [];

        $virtualAccount->update([
            'status' => 'active',
            'provider_status' => $data['status'],
            'account_name' => $accountInfo['accountName'] ?? $virtualAccount->account_name,
            'account_number' => $accountInfo['accountNumber'] ?? $virtualAccount->account_number,
            'bank_name' => $accountInfo['bankName'] ?? $virtualAccount->bank_name,
            'bank_code' => $accountInfo['bankCode'] ?? $virtualAccount->bank_code,
            'country_code' => $accountInfo['countryCode'] ?? $virtualAccount->country_code,
            'iban' => $otherInfo['iban'] ?? $virtualAccount->iban,
            'sort_code' => $otherInfo['sortCode'] ?? $virtualAccount->sort_code,
            'swift_code' => $otherInfo['bankSwiftCode'] ?? $virtualAccount->swift_code,
            'is_permanent' => $data['isPermanent'] ?? $virtualAccount->is_permanent,
            'account_type' => $data['accountType'] ?? $virtualAccount->account_type,
            'virtual_account_type' => $data['virtualAccountType'] ?? $virtualAccount->virtual_account_type,
        ]);
    }

    protected function handleVirtualAccountDeclined(array $data): void
    {
        $virtualAccount = VirtualAccount::where('provider_reference', $data['id'])->first();

        if (! $virtualAccount) {
            Log::error('Virtual account not found for declined event', [
                'provider_reference' => $data['id'],
            ]);

            return;
        }

        $virtualAccount->update([
            'status' => 'declined',
            'provider_status' => $data['status'],
        ]);

        Log::info('Virtual account declined', [
            'virtual_account_id' => $virtualAccount->id,
            'provider_reference' => $data['id'],
            'reason' => $data['reason'] ?? 'Not provided',
        ]);
    }

    protected function handleVirtualAccountClosed(array $data): void
    {
        $virtualAccount = VirtualAccount::where('provider_reference', $data['id'])->first();

        if (! $virtualAccount) {
            Log::error('Virtual account not found for closed event', [
                'provider_reference' => $data['id'],
            ]);

            return;
        }

        $accountInfo = $data['accountInformation'] ?? [];

        $virtualAccount->update([
            'status' => 'closed',
            'provider_status' => $data['status'],
        ]);

        Log::info('Virtual account closed', [
            'virtual_account_id' => $virtualAccount->id,
            'provider_reference' => $data['id'],
            'reason' => $data['reason'] ?? 'Not provided',
        ]);
    }
}
