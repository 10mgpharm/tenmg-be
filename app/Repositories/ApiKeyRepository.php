<?php

namespace App\Repositories;

use App\Models\ApiKey;

class ApiKeyRepository
{
    public function verifyApiKey(string $key, string $secret): ?ApiKey
    {
        return ApiKey::where('key', $key)
            ->where('secret', $secret)
            ->first();
    }
}
