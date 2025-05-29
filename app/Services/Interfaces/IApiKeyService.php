<?php

namespace App\Services\Interfaces;

use App\Models\ApiKey;
use App\Models\Business;
use Illuminate\Http\Request;

interface IApiKeyService
{
    public function getVendorKeys(Business $business): ?ApiKey;

    public function generateNewKeys(Business $business, string $type, string $environment): ?string;

    public function updateApiKeyConfig(Business $business, string $environment, string $webhook_url, string $callback_url): ?ApiKey;

    public function getVendorsWithAccess(Business $business): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    public function revokeApiKey(Request $request);
}
