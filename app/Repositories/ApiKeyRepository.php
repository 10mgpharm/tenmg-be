<?php

namespace App\Repositories;

use App\Models\ApiKey;
use App\Models\Business;

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

        // Generate unique encryption keys
        do {
            $encryptionKey = bin2hex(random_bytes(16));
        } while (ApiKey::where('encryption_key', $encryptionKey)->exists());

        do {
            $testEncryptionKey = bin2hex(random_bytes(16));
        } while (ApiKey::where('test_encryption_key', $testEncryptionKey)->exists());

        $data = [
            'key' => 'key_'.$keyPart,
            'secret' => 'sec_'.$secretPart,
            'test_key' => 'test_key_'.$keyPart,
            'test_secret' => 'test_sec_'.$secretPart,
            'encryption_key' => $encryptionKey,
            'test_encryption_key' => $testEncryptionKey,
        ];

        return ApiKey::updateOrCreate(
            ['business_id' => $adminBusiness->id],
            $data
        );
    }
}
