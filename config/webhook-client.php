<?php

return [
    'configs' => [
        [
            /*
             * This package supports multiple webhook receiving endpoints. If you only have
             * one endpoint receiving webhooks, you can use 'default'.
             */
            'name' => 'fincra',

            /*
             * We expect that every webhook call will be signed using a secret. This secret
             * is used to verify that the payload has not been tampered with.
             */
            'signing_secret' => env('FINCRA_WEBHOOK_SECRET'),

            /*
             * The name of the header containing the signature.
             */
            'signature_header_name' => 'signature',

            /*
             *  This class will verify that the content of the signature header is valid.
             *
             * It should implement \Spatie\WebhookClient\SignatureValidator\SignatureValidator
             */
            'signature_validator' => \App\Webhooks\Fincra\FincraSignatureValidator::class,

            /*
             * This class determines if the webhook call should be stored and processed.
             */
            'webhook_profile' => \App\Webhooks\Fincra\FincraWebhookProfile::class,

            /*
             * This class determines the response on a valid webhook call.
             */
            'webhook_response' => \App\Webhooks\Fincra\FincraWebhookResponse::class,

            /*
             * The classname of the model to be used to store webhook calls. The class should
             * be equal or extend Spatie\WebhookClient\Models\WebhookCall.
             */
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,

            /*
             * In this array, you can pass the headers that should be stored on
             * the webhook call model when a webhook comes in.
             *
             * To store all headers, set this value to `*`.
             */
            'store_headers' => [
                'signature',
            ],

            /*
             * The class name of the job that will process the webhook request.
             *
             * This should be set to a class that extends \Spatie\WebhookClient\Jobs\ProcessWebhookJob.
             */
            'process_webhook_job' => \App\Webhooks\Fincra\Handlers\FincraWebhookDispatcher::class,
        ],

        // SafeHaven webhook configuration
        [
            'name' => 'safehaven',
            'signing_secret' => env('SAFEHAVEN_WEBHOOK_SECRET'),
            'signature_header_name' => 'signature',
            'signature_validator' => \App\Webhooks\SafeHaven\SafeHavenSignatureValidator::class,
            'webhook_profile' => \App\Webhooks\SafeHaven\SafeHavenWebhookProfile::class,
            'webhook_response' => \App\Webhooks\SafeHaven\SafeHavenWebhookResponse::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [
                'signature',
                'x-forwarded-for',
                'user-agent',
            ],
            'process_webhook_job' => \App\Webhooks\SafeHaven\Handlers\SafeHavenWebhookDispatcher::class,
        ],
        // Mono webhook configuration
        [
            'name' => 'mono',
            'signing_secret' => env('MONO_WEBHOOK_SECRET'),
            'signature_header_name' => 'mono-webhook-secret',
            'signature_validator' => \App\Webhooks\Mono\MonoSignatureValidator::class,
            'webhook_profile' => \App\Webhooks\Mono\MonoWebhookProfile::class,
            'webhook_response' => \App\Webhooks\Mono\MonoWebhookResponse::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [
                'mono-webhook-secret',
                'user-agent',
            ],
            'process_webhook_job' => \App\Webhooks\Mono\Handlers\MonoWebhookDispatcher::class,
        ],
    ],

    /*
     * The integer amount of days after which models should be deleted.
     *
     * It deletes all records after 30 days. Set to null if no models should be deleted.
     */
    'delete_after_days' => 30,

    /*
     * Should a unique token be added to the route name
     */
    'add_unique_token_to_route_name' => false,
];
