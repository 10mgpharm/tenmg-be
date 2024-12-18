<?php

namespace App\Services\Admin;

use App\Enums\StatusEnum;
use App\Models\EcommercePackage;
use App\Models\User;
use App\Services\Interfaces\IEcommercePackageService;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EcommercePackageService implements IEcommercePackageService
{
    /**
     * @inheritDoc
     */
    public function store(array $validated, User $user): ?EcommercePackage
    {
        try {
            return DB::transaction(function () use ($validated, $user) {
                return EcommercePackage::create(
                    [
                        'name' => $validated['name'],
                        'business_id' => $user->ownerBusinessType?->id ?: $user->businesses()
                            ->firstWhere('user_id', $user->id)?->id,
                        'created_by_id' => $user->id,
                        'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                        'active' => true,
                    ]
                );
            });
        } catch (Exception $e) {
            throw new Exception('Failed to create a presentation: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function update(array $validated, User $user, EcommercePackage $presentation): ?bool
    {
        try {
            return DB::transaction(function () use ($validated, $user, $presentation) {
                return $presentation->update([
                    ...$validated,
                    'status' => $validated['status'] ?? $presentation->status,
                    'active' => $validated['active'] ?? $presentation->active,
                    'slug' => Str::slug($validated['name'] ?? $presentation->name),
                    'updated_by_id' => $user->id,
                ]);
            });
        } catch (Exception $e) {
            throw new Exception('Failed to update presentation: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc 
     */
    
    public function delete(EcommercePackage $presentation): bool
    {
        if ($presentation->products()->exists()) {
            return false; // Prevent deletion
        }

        return $presentation->delete();
    }
}
