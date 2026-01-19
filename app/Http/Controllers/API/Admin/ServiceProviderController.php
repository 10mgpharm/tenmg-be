<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceProvider\ListServiceProvidersRequest;
use App\Http\Requests\ServiceProvider\ShowServiceProviderRequest;
use App\Http\Requests\ServiceProvider\UpdateServiceProviderRequest;
use App\Http\Resources\ServiceProvider\ServiceProviderResource;
use App\Models\ServiceProvider;
use Illuminate\Http\JsonResponse;

class ServiceProviderController extends Controller
{
    /**
     * List service providers (admin only).
     */
    public function index(ListServiceProvidersRequest $request): JsonResponse
    {
        $query = ServiceProvider::query()
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->has('is_virtual_account_provider'), function ($query) use ($request) {
                $query->where('is_virtual_account_provider', (bool) $request->boolean('is_virtual_account_provider'));
            })
            ->when($request->has('is_bank_payout_provider'), function ($query) use ($request) {
                $query->where('is_bank_payout_provider', (bool) $request->boolean('is_bank_payout_provider'));
            });

        $perPage = $request->input('per_page', 15);

        $providers = $query->paginate($perPage)->withQueryString();

        return $this->returnJsonResponse(
            message: 'Service providers successfully fetched.',
            data: ServiceProviderResource::collection($providers)->response()->getData(true)
        );
    }

    /**
     * Show a single service provider (admin only).
     */
    public function show(ShowServiceProviderRequest $request, ServiceProvider $serviceProvider): JsonResponse
    {
        return $this->returnJsonResponse(
            message: 'Service provider successfully fetched.',
            data: new ServiceProviderResource($serviceProvider)
        );
    }

    /**
     * Update a service provider (admin only).
     */
    public function update(UpdateServiceProviderRequest $request, ServiceProvider $serviceProvider): JsonResponse
    {
        $serviceProvider->fill($request->validated());
        $serviceProvider->save();

        return $this->returnJsonResponse(
            message: 'Service provider successfully updated.',
            data: new ServiceProviderResource($serviceProvider->refresh())
        );
    }
}
