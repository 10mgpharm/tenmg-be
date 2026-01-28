<?php

declare(strict_types=1);

namespace App\Webhooks\Fincra;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookResponse\RespondsToWebhook;

class FincraWebhookResponse implements RespondsToWebhook
{
    public function respondToValidWebhook(Request $request, WebhookConfig $config): Response
    {
        return new Response(
            json_encode([
                'message' => 'Webhook received successfully',
                'timestamp' => now()->toIso8601String(),
            ]),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json']
        );
    }
}
