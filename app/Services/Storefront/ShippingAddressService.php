<?php

namespace App\Services\Storefront;

use App\Http\Resources\Storefront\ShippingAddressResource;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Services\Interfaces\Storefront\IShippingAddressServiceService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ShippingAddressService implements IShippingAddressServiceService
{
    public function __construct() {}

    /**
     * @inheritDoc
     */
    public function store(array $validated, User $user): ShippingAddress
    {
        try {
            return DB::transaction(function () use ($validated, $user) {
                $validated['business_id'] = $user->ownerBusinessType?->id
                    ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

                return ShippingAddress::create([...$validated, 'created_by_id' => $user->id]);
            });
        } catch (Exception $e) {
            throw new Exception('Failed to add shipping address: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function update(array $validated, User $user, ShippingAddress $shipping_address): bool
    {
        try {
            return DB::transaction(function () use ($validated, $user, $shipping_address) {
                $validated = array_filter($validated);

                $validated['business_id'] = $user->ownerBusinessType?->id
                    ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

                return $shipping_address->update([...$validated, 'updated_by_id' => $user->id]);
            });
        } catch (Exception $e) {
            throw new Exception('Failed to update shipping address: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function search(Request $request): LengthAwarePaginator
    {
        $user = $request->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $query = ShippingAddress::where('business_id', $business_id)
            ->when($request->input('search'), fn($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($request->input('address'), fn($q, $address) => $q->where('address', 'like', "%{$address}%"))
            ->when($request->input('country'), fn($q, $country) => $q->where('country', 'like', "%{$country}%"));

        // Apply sorting
        if ($request->has('sort') && $request->has('order')) {
            $sortColumn = $request->input('sort');
            $sortOrder = $request->input('order');
            $validColumns = ['name', 'address', 'country', 'created_at'];

            if (in_array($sortColumn, $validColumns) && in_array(strtolower($sortOrder), ['asc', 'desc'])) {
                $query->orderBy($sortColumn, $sortOrder);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($request->input('perPage', 10))->withQueryString()
            ->through(fn(ShippingAddress $item) => new ShippingAddressResource($item));
    }
}
