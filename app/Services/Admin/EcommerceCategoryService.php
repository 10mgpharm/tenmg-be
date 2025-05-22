<?php

namespace App\Services\Admin;

use App\Enums\StatusEnum;
use App\Helpers\UtilityHelper;
use App\Models\EcommerceCategory;
use App\Models\User;
use App\Services\Interfaces\IEcommerceCategoryService;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EcommerceCategoryService implements IEcommerceCategoryService
{
    /**
     * Store a new ecommerce category.
     *
     * This method validates the incoming data and creates a new
     * ecommerce category associated with the given user. It uses a database
     * transaction to ensure data integrity, creating the category with the
     * specified attributes and default values if not provided.
     *
     * @param array $validated The validated data for creating the category.
     * @param User $user The user creating the category.
     * @return EcommerceCategory|null The created category or null on failure.
     * @throws Exception If the category creation fails.
     */
    public function store(array $validated, User $user): ?EcommerceCategory
    {
        try {
            return DB::transaction(function () use ($validated, $user) {

                 // Doc: when business is null that means the category and its dependencies are for global used and created by admin
                if ($user && $user->hasRole('admin')) {
                    $validated['business_id'] = null;
                } else {
                    $validated['business_id'] = $user->ownerBusinessType?->id ?: $user->businesses()
                        ->firstWhere('user_id', $user->id)?->id;
                }

                $validated['status'] = $validated['status'] ?? (isset($validated['active']) && $validated['active'] === false 
                ? StatusEnum::INACTIVE->value 
                : StatusEnum::ACTIVE->value);

                return $user->categories()->create([
                    ...$validated,
                    'active' => in_array($validated['status'], StatusEnum::actives()) 
                    ? ($validated['active'] ?? true) 
                    : false,
                    'slug' =>  UtilityHelper::generateSlug('CAT'),
                    'business_id' => $validated['business_id'],
                    'created_by_id' => $user->id,
                ]);
            });
        } catch (Exception $e) {
            throw new Exception('Failed to create a category: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing ecommerce category.
     *
     * This method validates the incoming data and updates the specified
     * ecommerce category. It uses a database transaction to ensure data integrity.
     * The category will be updated with the new attributes provided in the
     * validated data, including the status and active state. If the name is
     * changed, the slug will be regenerated.
     *
     * @param array $validated The validated data for updating the category.
     * @param User $user The user updating the category.
     * @param EcommerceCategory $category The category to be updated.
     * @return bool|null True if the update was successful, null on failure.
     * @throws Exception If the category update fails.
     */
    public function update(array $validated, User $user, EcommerceCategory $category): ?bool
    {
        try {
            return DB::transaction(function () use ($validated, $user, $category) {

                $validated['status'] = isset($validated['active']) && $validated['active'] === true 
                ? (isset($validated['status']) && in_array($validated['status'], StatusEnum::actives()) 
                    ? $validated['status'] 
                    : $validated['status'] ?? StatusEnum::ACTIVE->value) 
                : ($validated['status'] ?? ($category->status ?? StatusEnum::INACTIVE->value));

            
            $validated['active'] = (in_array($validated['status'], StatusEnum::actives()))
                ? ($validated['active'] ?? true )
                : (false);

                return $category->update([
                    ...$validated,
                    'updated_by_id' => $user->id,
                ]);
            });
        } catch (Exception $e) {
            throw new Exception('Failed to update category: ' . $e->getMessage());
        }
    }

    /**
     * Delete an existing ecommerce category.
     *
     * Prevents deletion if the category has associated products.
     *
     * @param EcommerceCategory $category The category to be deleted.
     * @return bool Returns true if the category was deleted, or false if it cannot be deleted due to associated products.
     */
    public function delete(EcommerceCategory $category): bool
    {
        // Check if the brand has associated products
        if ($category->products()->exists()) {
            return false; // Prevent deletion
        }

        // Proceed with deletion
        return $category->forceDelete();
    }
}
