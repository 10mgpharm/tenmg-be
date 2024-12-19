<?php

namespace App\Services\Admin;

use App\Enums\StatusEnum;
use App\Models\EcommerceMedicationType;
use App\Models\User;
use App\Services\Interfaces\IEcommerceMedicationTypeService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

                // create medication type
                $medication_type = $user->medicationTypes()->create([
                    'name' => $validated['name'],
                    'status' => $validated['status'] ?? StatusEnum::APPROVED->value,
                    'active' => $validated['active'] ?? false,
                    'slug' => Str::slug($validated['name']),
                    'business_id' => $user->ownerBusinessType?->id ?: $user->businesses()
                        ->firstWhere('user_id', $user->id)?->id,
                ]);

                // create variations for that medication type
                if (isset($validated['variations'])) {
                    foreach ($validated['variations'] as $variation) {
                        // check if variation already exists
                        $existing_variation = $medication_type->variations()
                            ->where('name', $variation['name'])
                            ->first();

                        if ($existing_variation) {
                            continue;
                        }

                        // TODO: Table changes
                        // delete ecommerce_package_id column on ecommerce_medication_variations table
                        // delete presentation column on ecommerce_medication_variations table
                        // change strength to ecommerce_measurement_id as foreign key nullable on ecommerce_medication_variations table

                        //TODO: check if $variation[presentation] Danjuma exist
                        // if exist, pick its id from ecommerce_presentations and use for ecommerce_presentation_id when inserting variation
                        // Table ecommerce_presentations, foreign_key on variations is  ecommerce_presentation_id

                        //TODO: check if $variation[measurement] Danjuma exist
                        // if exist, pick its id from ecommerce_measurements and use for ecommerce_measurement_id  when inserting variation
                        // Table ecommerce_measurements, foreign_key on variations is

                        $medication_type->variations()->create([
                            'name' => $variation['name'],
                            // TODO: add necessary columns
                            // ecommerce_presentation_id
                            // ecommerce_measurement_id
                            // -- strength_value
                            // -- package_per_roll
                            // -- weight
                            // -- status
                            // -- active
                        ]);
                    }
                }

                return $user->medicationTypes()->create([
                    ...$validated,
                    'status' => $validated['status'] ?? StatusEnum::APPROVED->value,
                    'active' => $validated['active'] ?? false,
                    'slug' => Str::slug($validated['name']),
                    'business_id' => $user->ownerBusinessType?->id ?: $user->businesses()
                        ->firstWhere('user_id', $user->id)?->id,
                ]);
            });
        } catch (Exception $e) {
            throw new Exception('Failed to create a medication type: '.$e->getMessage());
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
                return $medication_type->update([
                    ...$validated,
                    'status' => $validated['status'] ?? StatusEnum::APPROVED->value,
                    'active' => $validated['active'] ?? false,
                    'slug' => Str::slug($validated['name'] ?? $medication_type->name),
                    'updated_by_id' => $user->id,
                ]);
            });
        } catch (Exception $e) {
            throw new Exception('Failed to update the medication type: '.$e->getMessage());
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
