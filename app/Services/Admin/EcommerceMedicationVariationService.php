<?php

namespace App\Services\Admin;

use App\Enums\StatusEnum;
use App\Models\EcommerceMedicationType;
use App\Models\EcommerceMedicationVariation;
use App\Models\EcommercePackage;
use App\Models\EcommercePresentation;
use App\Models\User;
use App\Services\Interfaces\IEcommerceMedicationVariationService;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EcommerceMedicationVariationService implements IEcommerceMedicationVariationService
{
    /**
     * @inheritDoc
     */
    public function store(array $validated, User $user): ?EcommerceMedicationVariation
    {
        try {
            return DB::transaction(function () use ($validated, $user) {

                $business = $user->ownerBusinessType
                ?? $user->businesses()->firstWhere('user_id', $user->id);
                $presentation = EcommercePresentation::where('name', $validated['presentation_name'])->first();
                $package = EcommercePackage::where('name', $validated['package_name'])->first();
                $medicationType = EcommerceMedicationType::where('name', $validated['medication_type_name'])->first();

                return EcommerceMedicationVariation::create(
                    [
                        'weight' => $validated['weight'],
                        'strength_value' => (int) preg_replace('/\D/', '', $validated['strength_value']) ?: 0,
                        'strength' => $validated['strength_value'],
                        'ecommerce_presentation_id' => $presentation->id,
                        'ecommerce_package_id' => $package->id,
                        'ecommerce_medication_type_id' => $medicationType->id,
                        'business_id' => $business?->id,
                        'updated_by_id' => $user->id,
                        'status' => $user->hasRole('admin') ? StatusEnum::ACTIVE->value : StatusEnum::APPROVED->value,
                        'active' => true,
                    ]
                );
            });
        } catch (Exception $e) {
            throw new Exception('Failed to create a medication variation: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function update(array $validated, User $user, EcommerceMedicationVariation $medication_variation): ?bool
    {
        try {
            return DB::transaction(function () use ($validated, $user, $medication_variation) {

                if(isset($validated['presentation_name'])){
                    $presentation = EcommercePresentation::where('name', $validated['presentation_name'])->first();
                }

                if(isset($validated['package_name'])){
                    $package = EcommercePackage::where('name', $validated['package_name'])->first();
                }

                if(isset($validated['medication_type_name'])){
                    $medicationType = EcommerceMedicationType::where('name', $validated['medication_type_name'])->first();
                }

                $fillable =  array_filter([
                    ...$validated,
                    'strength_value' => (int) preg_replace('/\D/', '', $validated['strength_value']) ?? null,
                    'strength' => $validated['strength_value'] ?? null,
                    'ecommerce_presentation_id' => $presentation->id ?? null,
                    'ecommerce_package_id' => $package->id ?? null,
                    'ecommerce_medication_type_id' => $medicationType->id ?? null,
                    'updated_by_id' => $user->id,
                ], fn($each) => $each !== null && $each !== false);

                return $medication_variation->update($fillable);
            });
        } catch (Exception $e) {
            throw new Exception('Failed to update medication variation: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc 
     */
    
    public function delete(EcommerceMedicationVariation $medication_variation): bool
    {
        if ($medication_variation->products()->exists()) {
            return false; // Prevent deletion
        }

        return $medication_variation->delete();
    }
}
