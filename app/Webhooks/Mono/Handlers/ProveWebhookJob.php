<?php

declare(strict_types=1);

namespace App\Webhooks\Mono\Handlers;

use App\Models\Business;
use App\Models\LenderKycSession;
use App\Services\Credit\LenderKycTierService;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class ProveWebhookJob extends ProcessWebhookJob
{
    public function __construct($webhookCall)
    {
        parent::__construct($webhookCall);
    }

    public function handle(): void
    {
        $payload = $this->webhookCall->payload;
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];

        if (! $event) {
            Log::error('Mono Prove webhook missing event', [
                'webhook_id' => $this->webhookCall->id,
            ]);

            return;
        }

        $reference = $data['reference'] ?? null;
        if (! $reference) {
            Log::warning('Mono Prove webhook missing reference', [
                'webhook_id' => $this->webhookCall->id,
                'data' => $data,
            ]);

            return;
        }

        $session = LenderKycSession::where('reference', $reference)->first();
        if (! $session) {
            Log::warning('Mono Prove webhook session not found', [
                'reference' => $reference,
                'webhook_id' => $this->webhookCall->id,
            ]);

            return;
        }

        $lenderBusiness = $session->lenderBusiness;
        if (! $lenderBusiness) {
            Log::error('Mono Prove webhook lender business not found', [
                'session_id' => $session->id,
                'reference' => $reference,
            ]);

            return;
        }

        $kycTierService = app(LenderKycTierService::class);

        // Handle different event types
        switch ($event) {
            case 'mono.prove.data_verification_successful':
                $this->handleVerificationSuccessful($session, $lenderBusiness, $data, $kycTierService);
                break;
            case 'mono.prove.data_verification_cancelled':
                $this->handleVerificationCancelled($session, $data);
                break;
            case 'mono.prove.data_verification_expired':
                $this->handleVerificationExpired($session, $data);
                break;
            case 'mono.prove.data_verification_rejected':
                $this->handleVerificationRejected($session, $data);
                break;
            case 'mono.prove.data_verification_awaiting_review':
                $this->handleVerificationAwaitingReview($session, $data);
                break;
            case 'mono.prove.data_verification_initiated':
                $this->handleVerificationInitiated($session, $data);
                break;
            default:
                Log::warning('Unhandled Mono Prove webhook event', [
                    'event' => $event,
                    'reference' => $reference,
                ]);
        }
    }

    /**
     * Handle successful verification
     */
    private function handleVerificationSuccessful(LenderKycSession $session, Business $lenderBusiness, array $data, LenderKycTierService $kycTierService): void
    {
        $updateData = [
            'status' => 'successful',
            'completed_at' => now(),
            'verified_at' => now(),
            'meta' => array_merge($session->meta ?? [], [
                'webhook_data' => $data,
                'webhook_received_at' => now()->toIso8601String(),
            ]),
        ];

        // Mark the tier as completed if kyc_level exists
        if ($session->kyc_level) {
            $updateData['completed_tier'] = $session->kyc_level;
            $kycTierService->markTierCompleted($lenderBusiness, $session->kyc_level);
        }

        $session->update($updateData);

        Log::info('Mono Prove verification successful', [
            'session_id' => $session->id,
            'reference' => $session->reference,
            'tier' => $session->kyc_level,
            'lender_business_id' => $lenderBusiness->id,
        ]);
    }

    /**
     * Handle cancelled verification
     */
    private function handleVerificationCancelled(LenderKycSession $session, array $data): void
    {
        $session->update([
            'status' => 'cancelled',
            'meta' => array_merge($session->meta ?? [], [
                'webhook_data' => $data,
                'cancellation_reason' => $data['reason'] ?? null,
                'webhook_received_at' => now()->toIso8601String(),
            ]),
        ]);

        Log::info('Mono Prove verification cancelled', [
            'session_id' => $session->id,
            'reference' => $session->reference,
            'reason' => $data['reason'] ?? null,
        ]);
    }

    /**
     * Handle expired verification
     */
    private function handleVerificationExpired(LenderKycSession $session, array $data): void
    {
        $session->update([
            'status' => 'expired',
            'meta' => array_merge($session->meta ?? [], [
                'webhook_data' => $data,
                'expired_at' => now()->toIso8601String(),
                'webhook_received_at' => now()->toIso8601String(),
            ]),
        ]);

        Log::info('Mono Prove verification expired', [
            'session_id' => $session->id,
            'reference' => $session->reference,
        ]);
    }

    /**
     * Handle rejected verification
     */
    private function handleVerificationRejected(LenderKycSession $session, array $data): void
    {
        $session->update([
            'status' => 'rejected',
            'meta' => array_merge($session->meta ?? [], [
                'webhook_data' => $data,
                'rejection_reason' => $data['reason'] ?? null,
                'webhook_received_at' => now()->toIso8601String(),
            ]),
        ]);

        Log::info('Mono Prove verification rejected', [
            'session_id' => $session->id,
            'reference' => $session->reference,
            'reason' => $data['reason'] ?? null,
        ]);
    }

    /**
     * Handle verification awaiting review
     */
    private function handleVerificationAwaitingReview(LenderKycSession $session, array $data): void
    {
        $session->update([
            'status' => 'awaiting_review',
            'meta' => array_merge($session->meta ?? [], [
                'webhook_data' => $data,
                'webhook_received_at' => now()->toIso8601String(),
            ]),
        ]);

        Log::info('Mono Prove verification awaiting review', [
            'session_id' => $session->id,
            'reference' => $session->reference,
        ]);
    }

    /**
     * Handle verification initiated
     */
    private function handleVerificationInitiated(LenderKycSession $session, array $data): void
    {
        $session->update([
            'status' => 'pending',
            'meta' => array_merge($session->meta ?? [], [
                'webhook_data' => $data,
                'webhook_received_at' => now()->toIso8601String(),
            ]),
        ]);

        Log::info('Mono Prove verification initiated', [
            'session_id' => $session->id,
            'reference' => $session->reference,
        ]);
    }
}
