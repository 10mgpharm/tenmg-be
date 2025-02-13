<?php

namespace App\Repositories;

use App\Models\ApiKey;
use App\Models\Business;
use Exception;

class ApiKeyRepository
{
    public function verifyApiKey(string $key, string $secret): ?ApiKey
    {
        return ApiKey::where('key', $key)
            ->where('secret', $secret)
            ->first();
    }

    public function createVendorApiKey(Business $adminBusiness): ?ApiKey
    {
        $hashedShortName = hash('sha256', $adminBusiness->short_name.\Illuminate\Support\Str::random(48));

        // Extract substrings safely
        $keyPart = substr($hashedShortName, 0, 24) ?: \Illuminate\Support\Str::random(24);
        $secretPart = substr($hashedShortName, 25, 48) ?: \Illuminate\Support\Str::random(48);

        // Generate different keyPart and secretPart for test environment
        $testKeyPart = \Illuminate\Support\Str::random(24);
        $testSecretPart = \Illuminate\Support\Str::random(48);

        // Generate unique encryption keys
        do {
            $encryptionKey = bin2hex(random_bytes(16));
        } while (ApiKey::where('encryption_key', $encryptionKey)->exists());

        do {
            $testEncryptionKey = bin2hex(random_bytes(16));
        } while (ApiKey::where('test_encryption_key', $testEncryptionKey)->exists());

        $data = [
            'key' => 'pk_live_'.$keyPart,
            'secret' => 'sk_live_'.$secretPart,
            'test_key' => 'pk_test_'.$testKeyPart,
            'test_secret' => 'sk_test_'.$testSecretPart,
            'encryption_key' => $encryptionKey,
            'test_encryption_key' => $testEncryptionKey,
        ];

        return ApiKey::updateOrCreate(
            ['business_id' => $adminBusiness->id],
            $data
        );
    }

    public function getVendorKeys(Business $business): ?ApiKey
    {
        return ApiKey::where('business_id', $business?->id)->first();
    }

    public function updateVendorKey(Business $business, array $data): ?ApiKey
    {
        $apiKey = ApiKey::where('business_id', $business?->id)->first();

        if ($apiKey) {
            $apiKey->update($data);

            return $apiKey;
        }

        return null;
    }

    public function verifyPublicKeyExist($publicKey): ?Business
    {
        $key = ApiKey::where('key', $publicKey)
            ->orWhere('test_key', $publicKey)
            ->first();

        if (! $key) {
            throw new Exception('Invalid key params');
        }

        return $key->business;
    }

    public function verifySecretKeyExist($secretKey): ?Business
    {
        $key = ApiKey::where('secret', $secretKey)
            ->orWhere('test_secret', $secretKey)
            ->first();

        if (! $key) {
            throw new Exception('Invalid key params');
        }

        return $key->business;
    }
}
