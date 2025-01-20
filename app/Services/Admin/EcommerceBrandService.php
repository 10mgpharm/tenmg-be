<?php

namespace App\Services\Admin;

use App\Enums\StatusEnum;
use App\Helpers\UtilityHelper;
use App\Models\EcommerceBrand;
use App\Models\User;
use App\Services\Interfaces\IEcommerceBrandService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EcommerceBrandService implements IEcommerceBrandService
{
    /**
     * Store a new ecommerce brand in the database.
     *
     * This method stores a new ecommerce brand using the provided validated data.
     * It wraps the operation in a database transaction to ensure atomicity.
     * The slug is generated automatically from the name if not provided.
     *
     * @param  array  $validated  Array of validated data, including fields like 'name', 'status', and 'active'.
     * @param  User  $user  The authenticated user who is creating the brand. The user is used to set the business and creator.
     * @return EcommerceBrand|null Returns the created EcommerceBrand instance on success, or null on failure.
     *
     * @throws \Exception Throws an exception if the transaction or creation process fails.
     */
    public function store(array $validated, User $user): ?EcommerceBrand
    {
        try {
            return DB::transaction(function () use ($validated, $user) {

                 // Doc: when business is null that means the brand and its dependencies are for global used and created by admin
                if ($user && $user->hasRole('admin')) {
                    $validated['business_id'] = null;
                } else {
                    $validated['business_id'] = $user->ownerBusinessType?->id ?: $user->businesses()
                        ->firstWhere('user_id', $user->id)?->id;
                }
                
                $validated['status'] = $validated['status'] ?? (isset($validated['active']) && $validated['active'] === false 
                ? StatusEnum::INACTIVE->value 
                : StatusEnum::ACTIVE->value);
            
                return $user->brands()->create([
                    ...$validated,
                    'active' => in_array($validated['status'], StatusEnum::actives()) 
                    ? ($validated['active'] ?? true) 
                    : false,
                    'slug' => UtilityHelper::generateSlug('BRD'),
                    'business_id' => $validated['business_id'],
                    'created_by_id' => $user->id,
                ]);
            });
        } catch (Exception $e) {
            throw new Exception('Failed to create a brand: '.$e->getMessage());
        }
    }

    /**
     * Update an existing ecommerce brand in the database.
     *
     * This method updates an existing ecommerce brand with new validated data. It handles updates within a database transaction to ensure data consistency.
     * If the name is updated, a new slug will be generated.
     *
     * @param  array  $validated  Array of validated data to update the brand, such as 'name', 'status', and 'active'.
     * @param  User  $user  The authenticated user performing the update. The user's ID is recorded as the updater.
     * @param  EcommerceBrand  $brand  The existing EcommerceBrand instance that needs to be updated.
     * @return bool|null Returns true if the brand is successfully updated, or null on failure.
     *
     * @throws \Exception Throws an exception if the transaction or update process fails.
     */
    public function update(array $validated, User $user, EcommerceBrand $brand): ?bool
    {
        try {
            return DB::transaction(function () use ($validated, $user, $brand) {

                $validated['status'] = isset($validated['active']) && $validated['active'] === true 
                ? (isset($validated['status']) && in_array($validated['status'], StatusEnum::actives()) 
                    ? $validated['status'] 
                    : $validated['status'] ?? StatusEnum::ACTIVE->value) 
                : ($validated['status'] ?? ($brand->status ?? StatusEnum::INACTIVE->value));

            
            $validated['active'] = (in_array($validated['status'], StatusEnum::actives()))
                ? ($validated['active'] ?? true )
                : (false);
            

                return $brand->update([
                    ...$validated,
                    'updated_by_id' => $user->id,
                ]);
            });
        } catch (Exception $e) {
            throw new Exception('Failed to update brand: '.$e->getMessage());
        }
    }

    /**
     * Delete an existing ecommerce brand.
     *
     * Prevents deletion if the brand has associated products.
     *
     * @param  EcommerceBrand  $brand  The brand to be deleted.
     * @return bool Returns true if the brand was deleted, or false if it cannot be deleted due to associated products.
     */
    public function delete(EcommerceBrand $brand): bool
    {
        // Check if the brand has associated products
        if ($brand->products()->exists()) {
            return false; // Prevent deletion
        }

        // Proceed with deletion
        return $brand->forceDelete();
    }
}
