<?php

namespace App\Services\Admin;

use App\Enums\StatusEnum;
use App\Models\EcommerceMeasurement;
use App\Models\User;
use App\Services\Interfaces\IEcommerceMeasurementService;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EcommerceMeasurementService implements IEcommerceMeasurementService
{
    /**
     * @inheritDoc
     */
    public function store(array $validated, User $user): ?EcommerceMeasurement
    {
        try {
            return DB::transaction(function () use ($validated, $user) {
                return EcommerceMeasurement::create(
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
            throw new Exception('Failed to create a measurement: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function update(array $validated, User $user, EcommerceMeasurement $measurement): ?bool
    {
        try {
            return DB::transaction(function () use ($validated, $user, $measurement) {
                return $measurement->update([
                    ...$validated,
                    'status' => $validated['status'] ?? $measurement->status,
                    'active' => $validated['active'] ?? $measurement->active,
                    'slug' => Str::slug($validated['name'] ?? $measurement->name),
                    'updated_by_id' => $user->id,
                ]);
            });
        } catch (Exception $e) {
            throw new Exception('Failed to update measurement: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc 
     */
    
    public function delete(EcommerceMeasurement $measurement): bool
    {
        if ($measurement->products()->exists()) {
            return false; // Prevent deletion
        }

        return $measurement->delete();
    }
}
