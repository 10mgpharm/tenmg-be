<?php

declare(strict_types=1);

namespace App\Webhooks\Mono;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookResponse\RespondsToWebhook;

class MonoWebhookResponse implements RespondsToWebhook
{
    public function respondToValidWebhook(Request $request, WebhookConfig $config): Response
    {
        // Mono expects 200 OK response
        return new Response(
            json_encode([
                'status' => 'success',
                'message' => 'Webhook received successfully',
                'timestamp' => now()->toIso8601String(),
            ]),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json']
        );
    }
}
