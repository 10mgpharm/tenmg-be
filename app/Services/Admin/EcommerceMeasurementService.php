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

                // Doc: when business is null that means the measurement and its dependencies are for global used and created by admin
                if ($user && $user->hasRole('admin')) {
                    $validated['business_id'] = null;
                } else {
                    $validated['business_id'] = $user->ownerBusinessType?->id ?: $user->businesses()
                        ->firstWhere('user_id', $user->id)?->id;
                }

                $validated['status'] = $validated['status'] ?? (isset($validated['active']) && $validated['active'] === false 
                ? StatusEnum::INACTIVE->value 
                : StatusEnum::ACTIVE->value);

                return EcommerceMeasurement::create(
                    [
                        ...$validated,
                        'active' => in_array($validated['status'], StatusEnum::actives()) 
                            ? ($validated['active'] ?? true) 
                            : false,
                        'created_by_id' => $user->id,
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

                $validated['status'] = isset($validated['active']) && $validated['active'] === true 
                ? (isset($validated['status']) && in_array($validated['status'], StatusEnum::actives()) 
                    ? $validated['status'] 
                    : $validated['status'] ?? StatusEnum::ACTIVE->value) 
                : ($validated['status'] ?? ($measurement->status ?? StatusEnum::INACTIVE->value));

            
            $validated['active'] = (in_array($validated['status'], StatusEnum::actives()) )
                ? ($validated['active'] ?? true )
                : (false);

                return $measurement->update([
                    ...$validated,
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
        if ($measurement->variations()->exists()) {
            return false; // Prevent deletion
        }

        return $measurement->forceDelete();
    }
}
