<?php

namespace App\Services\Admin;

use App\Enums\StatusEnum;
use App\Models\EcommercePresentation;
use App\Models\User;
use App\Services\Interfaces\IEcommercePresentationService;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EcommercePresentationService implements IEcommercePresentationService
{
    /**
     * @inheritDoc
     */
    public function store(array $validated, User $user): ?EcommercePresentation
    {
        try {
            return DB::transaction(function () use ($validated, $user) {
                return EcommercePresentation::create(
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
    public function update(array $validated, User $user, EcommercePresentation $presentation): ?bool
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
    
    public function delete(EcommercePresentation $presentation): bool
    {
        if ($presentation->products()->exists()) {
            return false; // Prevent deletion
        }

        return $presentation->delete();
    }
}
