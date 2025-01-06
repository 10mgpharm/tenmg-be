<?php

namespace App\Http\Controllers\API\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\DeleteShippingAddressRequest;
use App\Http\Requests\Storefront\ListShippingAddressRequest;
use App\Http\Requests\Storefront\ShowShippingAddressRequest;
use App\Http\Requests\Storefront\StoreShippingAddressRequest;
use App\Http\Requests\Storefront\UpdateShippingAddressRequest;
use App\Http\Resources\Storefront\ShippingAddressResource;
use App\Models\ShippingAddress;
use App\Services\Storefront\ShippingAddressService;
use Illuminate\Http\JsonResponse;

class ShippingAddressController extends Controller
{
    public function __construct(private ShippingAddressService $shippingAddress) {}

    /**
     * Retrieve a list of shipping addresses for the authenticated user's business.
     *
     * @param  ListShippingAddressRequest  $request  Validated request instance.
     * @return JsonResponse The list of shipping addresses.
     */
    public function index(ListShippingAddressRequest $request): JsonResponse
    {
        $shippingAddresses = ShippingAddress::latest()->businesses()->get();

        return $this->returnJsonResponse(
            message: 'Shipping addresses successfully fetched.',
            data: ShippingAddressResource::collection($shippingAddresses)
        );
    }

    /**
     * Store a new shipping address.
     *
     * @param  StoreShippingAddressRequest  $request  Validated request instance.
     * @return JsonResponse The created shipping address.
     */
    public function store(StoreShippingAddressRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $shippingAddress = $this->shippingAddress->store($validated, $user);

        if (!$shippingAddress) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t add a shipping address at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'Shipping address successfully created.',
            data: new ShippingAddressResource($shippingAddress)
        );
    }

    /**
     * Display a specific shipping address.
     *
     * @param  ShowShippingAddressRequest  $request  Validated request instance.
     * @param  ShippingAddress  $shippingAddress  The shipping address to display.
     * @return JsonResponse The shipping address details.
     */
    public function show(ShowShippingAddressRequest $request, ShippingAddress $shippingAddress): JsonResponse
    {
        return $this->returnJsonResponse(
            message: 'Shipping address successfully fetched.',
            data: new ShippingAddressResource($shippingAddress)
        );
    }

    /**
     * Update an existing shipping address.
     *
     * @param  UpdateShippingAddressRequest  $request  Validated request instance.
     * @param  ShippingAddress  $shippingAddress  The shipping address to update.
     * @return JsonResponse The updated shipping address.
     */
    public function update(UpdateShippingAddressRequest $request, ShippingAddress $shippingAddress): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $isUpdated = $this->shippingAddress->update($validated, $user, $shippingAddress);

        if (!$isUpdated) {
            return $this->returnJsonResponse(
                message: 'Oops, can\'t update the shipping address at the moment. Please try again later.'
            );
        }

        return $this->returnJsonResponse(
            message: 'Shipping address successfully updated.',
            data: new ShippingAddressResource($shippingAddress->refresh())
        );
    }

    /**
     * Search for shipping addresses based on provided criteria.
     *
     * @param  ListShippingAddressRequest  $request  Validated request instance.
     * @return JsonResponse The filtered list of shipping addresses.
     */
    public function search(ListShippingAddressRequest $request): JsonResponse
    {
        $shippingAddresses = $this->shippingAddress->search($request);

        return $this->returnJsonResponse(
            message: 'Shipping addresses successfully fetched.',
            data: $shippingAddresses
        );
    }

    /**
     * Delete a specific shipping address.
     *
     * @param  DeleteShippingAddressRequest  $request  Validated request instance.
     * @param  ShippingAddress  $shippingAddress  The shipping address to delete.
     * @return JsonResponse Confirmation of deletion.
     */
    public function destroy(DeleteShippingAddressRequest $request, ShippingAddress $shippingAddress): JsonResponse
    {
        $shippingAddress->delete();

        return $this->returnJsonResponse(
            message: 'Shipping address successfully deleted.'
        );
    }
}
