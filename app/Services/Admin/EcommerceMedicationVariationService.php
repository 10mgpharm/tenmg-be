<?php

namespace App\Services\Admin;

use App\Enums\StatusEnum;
use App\Models\EcommerceMeasurement;
use App\Models\EcommerceMedicationType;
use App\Models\EcommerceMedicationVariation;
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
                $measurement = EcommerceMeasurement::where('name', $validated['measurement_name'] ?? '')->first();
                $medicationType = EcommerceMedicationType::where('name', $validated['medication_type_name'])->first();

                return EcommerceMedicationVariation::firstOrCreate(
                    [
                        'ecommerce_presentation_id' => $presentation->id,
                        'ecommerce_medication_type_id' => $medicationType->id,
                        'ecommerce_measurement_id' => $measurement->id,
                        'strength_value' =>  $validated['strength_value'],
                        'weight' => $validated['weight'],
                        'package_per_roll' =>  $validated['package_per_roll'] ?? null,
                        'business_id' => $business?->id,
                    ],
                    [
                        'created_by_id' => $user->id,
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

                if(isset($validated['measurement_name'])){
                    $measurement = EcommerceMeasurement::where('name', $validated['measurement_name'])->first();
                }

                if(isset($validated['medication_type_name'])){
                    $medicationType = EcommerceMedicationType::where('name', $validated['medication_type_name'])->first();
                }

                $fillable =  array_filter([
                    ...$validated,
                    'strength_value' => $validated['strength_value'],
                    'ecommerce_presentation_id' => $presentation->id ?? null,
                    'ecommerce_measurement_id' => $measurement->id ?? null,
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
