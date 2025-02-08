<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\Business;
use App\Repositories\ApiKeyRepository;
use App\Services\Interfaces\IApiKeyService;
use Exception;

class ApiKeyService implements IApiKeyService
{
    public function __construct(
        private ApiKeyRepository $apiKeyRepository,
    ) {}

    public function getVendorKeys(Business $business): ?ApiKey
    {
        return $this->apiKeyRepository->getVendorKeys($business);
    }

    public function generateNewKeys(Business $business, string $type, string $environment): ?string
    {
        $hashedShortName = hash('sha256', $business->short_name.\Illuminate\Support\Str::random(48));

        $generatedKey = '';

        if ($environment == 'test') {
            $testKeyPart = \Illuminate\Support\Str::random(24);
            $testSecretPart = \Illuminate\Support\Str::random(48);
            if ($type == 'public') {
                $generatedKey = 'pk_test_'.$testKeyPart;
                $data = ['test_key' => $generatedKey];
            } else {  // secret
                $generatedKey = 'sk_test_'.$testSecretPart;
                $data = ['test_secret' => $generatedKey];
            }
        } else { // live
            $keyPart = substr($hashedShortName, 0, 24) ?: \Illuminate\Support\Str::random(24);
            $secretPart = substr($hashedShortName, 25, 48) ?: \Illuminate\Support\Str::random(48);

            if ($type == 'public') {
                $generatedKey = 'pk_live_'.$keyPart;
                $data = ['key' => $generatedKey];
            } else { //secret
                $generatedKey = 'sk_live_'.$secretPart;
                $data = ['secret' => $generatedKey];
            }
        }

        $apiKey = $this->apiKeyRepository->updateVendorKey($business, $data);

        return $apiKey ? $generatedKey : throw new Exception('Unable to generate new key');
    }

    public function updateApiKeyConfig(Business $business, string $environment, string $webhookUrl, string $callbackUrl): ?ApiKey
    {
        $hashedShortName = hash('sha256', $business->short_name.\Illuminate\Support\Str::random(48));

        $apiKey = $this->apiKeyRepository->updateVendorKey($business, $environment == 'test' ?
            [
                'test_webhook_url' => $webhookUrl,
                'test_callback_url' => $callbackUrl,
            ] : [
                'webhook_url' => $webhookUrl,
                'callback_url' => $callbackUrl,
            ]
        );

        return $apiKey;
    }
}
