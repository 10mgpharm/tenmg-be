<?php

declare(strict_types=1);

namespace App\Webhooks\Mono;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

class MonoSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        // Mono uses simple string comparison, not HMAC
        $webhookSecret = $request->header('mono-webhook-secret');
        $expectedSecret = $config->signingSecret;

        if (! $webhookSecret || ! $expectedSecret) {
            Log::warning('Mono webhook missing secret header or config', [
                'has_header' => ! empty($webhookSecret),
                'has_config' => ! empty($expectedSecret),
                'ip' => $request->ip(),
            ]);

            return false;
        }

        // Use hash_equals for constant-time comparison to prevent timing attacks
        $isValid = hash_equals($expectedSecret, $webhookSecret);

        if (! $isValid) {
            Log::warning('Mono webhook secret validation failed', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return $isValid;
    }
}
