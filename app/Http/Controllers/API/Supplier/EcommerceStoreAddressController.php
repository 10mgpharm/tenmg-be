<?php

namespace App\Http\Controllers\API\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreEcommerceStoreAddressRequest;
use App\Http\Requests\Supplier\UpdateEcommerceStoreAddressRequest;
use App\Http\Resources\Supplier\EcommerceStoreAddressResource;
use App\Models\EcommerceStoreAddress;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EcommerceStoreAddressController extends Controller
{
    /**
     * Retrieve a paginated list of store addresses.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $business_id = $user->ownerBusinessType?->id
        ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $store_addresses = EcommerceStoreAddress::query()->where('business_id', $business_id)->latest('id')
            ->paginate($request->get('perPage', 20))
            ->withQueryString()
            ->through(fn(EcommerceStoreAddress $item) => new EcommerceStoreAddressResource($item));

        return $this->returnJsonResponse(
            message: "Store addresses successfully fetched.",
            data: $store_addresses
        );
    }

    /**
     * Show a specific store address.
     */
    public function show(EcommerceStoreAddress $store_address): JsonResponse
    {
        return $this->returnJsonResponse(
            message: "Store address successfully fetched.",
            data: new EcommerceStoreAddressResource($store_address)
        );
    }

    /**
     * Store a store address.
     */
    public function store(StoreEcommerceStoreAddressRequest $request): JsonResponse
    {
        
        try {
            $user = $request->user();
            $validated = $request->validated();

            $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;
            $validated['business_id'] = $business_id;

            $store_address = DB::transaction(fn ()  => EcommerceStoreAddress::create($validated));
        
            if(!$store_address) {
                return $this->returnJsonResponse(
                    message: "Ops, can't create store address at the moment. Please try again later.",
                );
            }

        return $this->returnJsonResponse(
            message: 'Store address successfully created.',
            data: new EcommerceStoreAddressResource($store_address)
        );

        } catch (Exception $e) {
            throw new Exception('Ops, failed to add store address at the moment. ' . $e->getMessage());
        }
    }

    /**
     * Update an existing store address.
     */
    public function update(UpdateEcommerceStoreAddressRequest $request, EcommerceStoreAddress $store_address): JsonResponse
    {
        $validated = $request->validated();

        if (! $store_address->update($validated)) {
            return $this->returnJsonResponse(
                message: "Oops, can't update store address at the moment. Please try again later."
            );
        }

        return $this->returnJsonResponse(
            message: "Store address successfully updated.",
            data: new EcommerceStoreAddressResource($store_address->refresh())
        );
    }

    /**
     * Search for store addresses based on query parameters.
     */
    public function search(Request $request): JsonResponse
    {
        $user = $request->user();
        $business_id = $user->ownerBusinessType?->id
        ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $query = EcommerceStoreAddress::query()->where('business_id', $business_id)
        ->when(
            $request->input('city'),
            fn($query, $city) => $query->where('city', 'like', "%{$city}%")
        )
        ->when(
            $request->input('state'),
            fn($query, $state) => $query->where('state', 'like', "%{$state}%")
        )
        ->when(
            $request->input('country'),
            fn($query, $country) => $query->where('country', 'like', "%{$country}%")
        );

        $store_addresses = $query->latest('id')->paginate($request->get('perPage', 20))->withQueryString()
            ->through(fn(EcommerceStoreAddress $item) => new EcommerceStoreAddressResource($item));

        return $this->returnJsonResponse(
            message: "Store addresses successfully fetched.",
            data: $store_addresses
        );
    }

    /**
     * Delete a store address.
     */
    public function destroy(EcommerceStoreAddress $store_address): JsonResponse
    {
        $store_address->delete();

        return $this->returnJsonResponse(
            message: "Store address successfully deleted."
        );
    }
}
