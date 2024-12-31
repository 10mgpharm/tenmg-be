<?php

namespace App\Services\Admin;

use App\Enums\StatusEnum;
use App\Helpers\UtilityHelper;
use App\Models\EcommerceMeasurement;
use App\Models\EcommerceMedicationType;
use App\Models\EcommerceMedicationVariation;
use App\Models\EcommercePresentation;
use App\Models\EcommerceProduct;
use App\Models\User;
use App\Services\Interfaces\IEcommerceMedicationTypeService;
use Exception;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class EcommerceMedicationTypeService implements IEcommerceMedicationTypeService
{
    /**
     * Store a new medication type in the database.
     *
     * @param  array  $validated  The validated data for creating the medication type.
     * @param  \App\Models\User  $user  The user creating the medication type.
     * @return \App\Models\EcommerceMedicationType|null Returns the created medication type model or null on failure.
     *
     * @throws \Exception If the transaction fails.
     */
    public function store(array $validated, User $user): ?EcommerceMedicationType
    {
        try {
            // Start a database transaction
            return DB::transaction(function () use ($validated, $user) {

                // Doc: when business is null that means the medication and its dependencies are for global used and created by admin
                if ($user && $user->hasRole('admin')) {
                    $validated['business_id'] = null;
                } else {
                    $validated['business_id'] = $user->ownerBusinessType?->id ?: $user->businesses()
                        ->firstWhere('user_id', $user->id)?->id;
                }

                // create medication type
                $medication_type = $user->medicationTypes()->create([
                    'name' => $validated['name'],
                    'status' => $validated['status'] ?? StatusEnum::APPROVED->value,
                    'active' => $validated['active'] ?? false,
                    'slug' => UtilityHelper::generateSlug('MED'),
                    'business_id' => $validated['business_id'],
                ]);

                // create variations for that medication type
                if (isset($validated['variations'])) {
                    foreach ($validated['variations'] as $variation) {
                        $presentation_id = null;
                        $presentationCheck = EcommercePresentation::where('name', $variation['presentation'])->first();
                        if ($presentationCheck) {
                            $presentation_id = $presentationCheck->id;
                        } else {
                            $presentation_id = EcommercePresentation::create([
                                'name' => $variation['presentation'],
                                'active' => 1,
                                'status' => StatusEnum::APPROVED->value,
                                'business_id' => $validated['business_id'],
                            ])->id;
                        }

                        $measurement_id = null;
                        $measurementCheck = EcommerceMeasurement::where('name', $variation['measurement'])->first();
                        if ($measurementCheck) {
                            $measurement_id = $measurementCheck->id;
                        } else {
                            $measurement_id = EcommerceMeasurement::create([
                                'name' => $variation['measurement'],
                                'active' => 1,
                                'status' => StatusEnum::APPROVED->value,
                                'business_id' => $validated['business_id'],
                                'created_by_id' => $user->id,
                            ])->id;
                        }

                        $medication_type->variations()->create([
                            'ecommerce_presentation_id' => $presentation_id,
                            'ecommerce_measurement_id' => $measurement_id,
                            'active' => 1,
                            'status' => StatusEnum::APPROVED->value,
                            'weight' => array_key_exists('weight', $variation) ? $variation['weight'] : null,
                            'strength_value' => $variation['strength_value'],
                            'package_per_roll' => $variation['package'],
                            'business_id' => $validated['business_id'],
                            'created_by_id' => $user->id,
                        ]);
                    }
                }

                return $medication_type;
            });
        } catch (Exception $e) {
            throw new Exception('Failed to create a medication type: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing medication type in the database.
     *
     * @param  array  $validated  The validated data for updating the medication type.
     * @param  \App\Models\User  $user  The user updating the medication type.
     * @param  \App\Models\EcommerceMedicationType  $medication_type  The medication type to update.
     * @return bool|null Returns true if the medication type was updated, null on failure.
     *
     * @throws \Exception If the transaction fails.
     */
    public function update(array $validated, User $user, EcommerceMedicationType $medication_type): ?bool
    {
        try {
            // Start a database transaction
            return DB::transaction(function () use ($validated, $user, $medication_type) {

                // Doc: when business is null that means the medication and its dependencies are for global used and created by admin
                if ($user && $user->hasRole('admin')) {
                    $validated['business_id'] = null;
                } else {
                    $validated['business_id'] = $user->ownerBusinessType?->id ?: $user->businesses()
                        ->firstWhere('user_id', $user->id)?->id;
                }

                $updated = $medication_type->update([
                    ...$validated,
                    'status' => $validated['status'] ?? StatusEnum::APPROVED->value,
                    'active' => $validated['active'] ?? false,
                    'updated_by_id' => $user->id,
                    'business_id' => $validated['business_id'],
                ]);

                // create variations if not exist or update for the selected medication type
                if (isset($validated['variations'])) {
                    $variationIds = [];

                    foreach ($validated['variations'] as $variation) {
                        $presentation_id = null;
                        $presentationCheck = EcommercePresentation::where('name', $variation['presentation'])->first();
                        if ($presentationCheck) {
                            $presentation_id = $presentationCheck->id;
                        } else {
                            $presentation_id = EcommercePresentation::create([
                                'name' => $variation['presentation'],
                                'active' => 1,
                                'status' => StatusEnum::APPROVED->value,
                                'business_id' => $validated['business_id'],
                            ])->id;
                        }

                        $measurement_id = null;
                        $measurementCheck = EcommerceMeasurement::where('name', $variation['measurement'])->first();
                        if ($measurementCheck) {
                            $measurement_id = $measurementCheck->id;
                        } else {
                            $measurement_id = EcommerceMeasurement::create([
                                'name' => $variation['measurement'],
                                'active' => 1,
                                'status' => StatusEnum::APPROVED->value,
                                'business_id' => $validated['business_id'],
                                'created_by_id' => $user->id,
                            ])->id;
                        }

                        $variationId = array_key_exists('id', $variation) ? $variation['id'] : null;

                        if ($variationId) {
                            $variationCheck = $medication_type->variations()->where('id', $variationId)->first();
                            $variationCheck->update([
                                'ecommerce_presentation_id' => $presentation_id,
                                'ecommerce_measurement_id' => $measurement_id,
                                'active' => 1,
                                'status' => StatusEnum::APPROVED->value,
                                'weight' => array_key_exists('weight', $variation) ? $variation['weight'] : null,
                                'strength_value' => $variation['strength_value'],
                                'package_per_roll' => $variation['package'],
                                'business_id' => $validated['business_id'],
                                'updated_by_id' => $user->id,
                            ]);
                            array_push($variationIds, $variationId);
                        } else {
                            $created = $medication_type->variations()->firstOrCreate(
                                [
                                    'ecommerce_presentation_id' => $presentation_id,
                                    'ecommerce_measurement_id' => $measurement_id,
                                    'weight' => array_key_exists('weight', $variation) ? $variation['weight'] : null,
                                    'strength_value' => $variation['strength_value'],
                                    'package_per_roll' => $variation['package'],
                                    'business_id' => $validated['business_id'],
                                ],
                                [
                                    'active' => 1,
                                    'status' => StatusEnum::APPROVED->value,
                                    'created_by_id' => $user->id,
                                ]
                            );

                            array_push($variationIds, $created->id);
                        }
                    }


                    if (
                        EcommerceProduct::whereIn('ecommerce_variation_id', $variationIds)
                        ->where(fn($query) => 
                        $query->orWhere('active', 1)
                        ->orWhereIn('status', [
                            StatusEnum::APPROVED->value,
                            StatusEnum::ACTIVE->value
                            ]))
                        ->exists()
                    ) {
                        throw new BadRequestHttpException('Cannot delete this variation because it has associated products.');
                    } else {
                        $medication_type->variations()->whereNotIn('id', $variationIds)->delete();
                    }
                }

                return $updated;
            });
        } catch (Exception $e) {
            throw new Exception('Failed to update the medication type: ' . $e->getMessage());
        }
    }

    /**
     * Delete an existing ecommerce medicationType.
     *
     * Prevents deletion if the medicationType has associated products.
     *
     * @param  EcommerceMedicationType  $medicationType  The medicationType to be deleted.
     * @return bool Returns true if the medicationType was deleted, or false if it cannot be deleted due to associated products.
     */
    public function delete(EcommerceMedicationType $medicationType): bool
    {
        // Check if the brand has associated products
        if ($medicationType->products()->exists()) {
            return false; // Prevent deletion
        }

        // Proceed with deletion
        return $medicationType->delete();
    }
}
