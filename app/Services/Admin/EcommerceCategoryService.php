<?php

namespace App\Services\Admin;

use App\Enums\StatusEnum;
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
                return $user->categories()->create([
                    ...$validated,
                    'status' => $validated['status'] ?? StatusEnum::APPROVED->value,
                    'active' => $validated['active'] ?? false,
                    'slug' => Str::slug($validated['name']),
                    'business_id' => $user->ownerBusinessType?->id ?: $user->businesses()
                    ->firstWhere('user_id', $user->id)?->id,
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
                return $category->update([
                    ...$validated,
                    'status' => $validated['status'] ?? $category->status,
                    'active' => $validated['active'] ?? $category->active,
                    'slug' => Str::slug($validated['name'] ?? $category->name),
                    'updated_by_id' => $user->id,
                ]);
            });
        } catch (Exception $e) {
            throw new Exception('Failed to update category: ' . $e->getMessage());
        }
    }
}
