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

                $validated['status'] = $validated['status'] ?? (isset($validated['active']) && $validated['active'] === false 
                ? StatusEnum::INACTIVE->value 
                : StatusEnum::ACTIVE->value);

                return EcommercePresentation::create(
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

                $validated['status'] = isset($validated['active']) && $validated['active'] === true 
                ? (isset($validated['status']) && in_array($validated['status'], StatusEnum::actives()) 
                    ? $validated['status'] 
                    : $validated['status'] ?? StatusEnum::ACTIVE->value) 
                : ($validated['status'] ?? ($presentation->status ?? StatusEnum::INACTIVE->value));

            
            $validated['active'] = (in_array($validated['status'], StatusEnum::actives()))
                ? ($validated['active'] ?? true )
                : (false);

                return $presentation->update([
                    ...$validated,
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
        if ($presentation->variations()->exists()) {
            return false; // Prevent deletion
        }

        return $presentation->forceDelete();
    }
}
