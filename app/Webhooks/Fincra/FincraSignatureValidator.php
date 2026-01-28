<?php

declare(strict_types=1);

namespace App\Webhooks\Fincra;

use App\Services\WebhookSecurityService;
use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

class FincraSignatureValidator implements SignatureValidator
{
    public function __construct(
        protected WebhookSecurityService $webhookSecurityService
    ) {}

    public function isValid(Request $request, WebhookConfig $config): bool
    {
        // Get Fincra specific configuration
        $fincraConfig = config('webhook-security.fincra');

        // Use the security service for validation
        $algorithm = $fincraConfig['signature_algorithm'] ?? 'sha512';
        $signatureHeaders = $fincraConfig['signature_headers'] ?? ['signature', 'X-Signature'];
        $checkTimestamp = $fincraConfig['check_timestamp'] ?? false;
        $timestampTolerance = $fincraConfig['timestamp_tolerance'] ?? 300;

        $isValid = $this->webhookSecurityService->validateSignature(
            $request,
            $config->signingSecret,
            $algorithm,
            $signatureHeaders,
            $checkTimestamp,
            $timestampTolerance
        );

        // Additional IP validation for Fincra
        if ($isValid) {
            $allowedIPs = $fincraConfig['allowed_ips'] ?? [];
            if (! empty($allowedIPs)) {
                $isValid = $this->webhookSecurityService->validateIP($request, $allowedIPs);
            }
        }

        return $isValid;
    }
}
