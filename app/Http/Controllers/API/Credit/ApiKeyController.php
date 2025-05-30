<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\GenerateApiKeyRequest;
use App\Http\Requests\Vendor\UpdateApiKeyUrlRequest;
use App\Http\Resources\ApiKeysResource;
use App\Services\Interfaces\IApiKeyService;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function __construct(public IApiKeyService $apiKeyService) {}

    public function index(Request $request)
    {
        $business = $request->user()->businesses->first();

        $apiKeys = $this->apiKeyService->getVendorKeys($business);

        return $this->returnJsonResponse(
            message: 'Api Keys successfully fetched.',
            data: $apiKeys
        );
    }

    public function regenerateKey(GenerateApiKeyRequest $request)
    {
        $request->validated();

        $business = $request->user()->businesses->first();
        $result = $this->apiKeyService->generateNewKeys($business, $request->type, $request->environment);

        return $this->returnJsonResponse(
            message: 'Api Keys successfully fetched.',
            data: [
                'type' => $request->type,
                'environment' => $request->environment,
                'value' => $result,
            ]
        );
    }

    public function update(UpdateApiKeyUrlRequest $request)
    {
        $request->validated();

        $business = $request->user()->businesses->first();
        $result = $this->apiKeyService->updateApiKeyConfig(
            $business,
            $request->environment,
            $request->webhookUrl,
            $request->callbackUrl,
            $request->transactionUrl
        );

        return $this->returnJsonResponse(
            message: 'Api Url configuration updated.',
            data: [
                'environment' => $request->environment,
                'value' => $result,
            ]
        );
    }

    public function getVendorsWithAccess(Request $request)
    {
        $vendorList = $this->apiKeyService->getVendorsWithAccess($request->perPage ?? 10);
        return $this->returnJsonResponse(
            message: 'Vendors with access successfully fetched.',
            data: ApiKeysResource::collection($vendorList)->response()->getData(true));

    }

    public function revokeApiKey(Request $request)
    {

        $request->validate([
            'businessId' => 'required|exists:businesses,id',
            'environment' => 'required|in:test,live',
        ]);

        $vendorList = $this->apiKeyService->revokeApiKey($request);
        return $this->returnJsonResponse(
            message: 'Vendors with access successfully fetched.',
            data: $vendorList);

    }
}
