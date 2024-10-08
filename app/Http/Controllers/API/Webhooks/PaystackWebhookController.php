<?php

namespace App\Http\Controllers\API\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function __construct(private PaystackService $paystackWebhookService) {}

    // Webhook to handle Paystack Direct Debit Mandates
    public function handle(Request $request)
    {
        // Verify the Paystack signature
        $secret = config('services.paystack.secret');
        $paystackSignature = $request->header('x-paystack-signature');
        $computedSignature = hash_hmac('sha512', $request->getContent(), $secret);

        if ($paystackSignature !== $computedSignature) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        // Log the request for debugging
        Log::info('Paystack Webhook Event', ['data' => $request->all()]);

        // Handle the event based on the type
        $event = $request->event;
        $payload = $request->data;

        switch ($event) {
            case 'direct_debit.authorization.created':
                $this->paystackWebhookService->handleMandateApproval($payload);
                break;

            case 'direct_debit.authorization.active':
                $chargeable = true;
                $this->paystackWebhookService->handleMandateApproval($payload, $chargeable);
                break;

            case 'charge.success':
                $this->paystackWebhookService->handleChargeSuccess($payload);
                break;

            default:
                Log::warning('Unhandled Paystack event', ['event' => $event]);
        }

        return response()->json(['status' => 'success'], 200);
    }
}
