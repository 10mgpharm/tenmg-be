<?php

namespace App\Services\Interfaces\Storefront;

use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface IShippingAddressServiceService
{
    /**
     * Store a new shipping address.
     *
     * This method creates a new shipping address for the specified user.
     * 
     * @param  array  $validated  The validated data for creating the shipping address.
     * @param  User   $user       The user creating the shipping address.
     * @return ShippingAddress    The created shipping address.
     *
     * @throws \Exception If the creation fails.
     */
    public function store(array $validated, User $user): ShippingAddress;

    /**
     * Update an existing shipping address.
     *
     * This method updates the specified shipping address with validated data.
     * 
     * @param  array             $validated         The validated data for updating the shipping address.
     * @param  User              $user              The user updating the shipping address.
     * @param  ShippingAddress   $shipping_address  The shipping address to be updated.
     * @return bool              True on success, false on failure.
     *
     * @throws \Exception If the update fails.
     */
    public function update(array $validated, User $user, ShippingAddress $shipping_address): bool;

    /**
     * Search for shipping addresses based on provided filters.
     *
     * This method retrieves a paginated list of shipping addresses for the user's business,
     * applying optional search and filter criteria.
     * 
     * @param  Request  $request  The request containing search parameters.
     * @return LengthAwarePaginator Paginated list of filtered shipping addresses.
     */
    public function search(Request $request): LengthAwarePaginator;
}
