<?php

namespace App\Services\Admin;

use App\Enums\StatusEnum;
use App\Models\EcommerceMedicationType;
use App\Models\User;
use App\Services\Interfaces\IEcommerceMedicationTypeService;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EcommerceMedicationTypeService implements IEcommerceMedicationTypeService
{
    /**
     * Store a new medication type in the database.
     *
     * @param  array  $validated  The validated data for creating the medication type.
     * @param  \App\Models\User  $user  The user creating the medication type.
     * @return \App\Models\EcommerceMedicationType|null Returns the created medication type model or null on failure.
     * @throws \Exception If the transaction fails.
     */
    public function store(array $validated, User $user): ?EcommerceMedicationType
    {
        try {
            // Start a database transaction
            return DB::transaction(function () use ($validated, $user) {
                return $user->medicationTypes()->create([
                    ...$validated,
                    'status' =>  $validated['status'] ?? StatusEnum::APPROVED->value,
                    'active' => $validated['active'] ?? false,
                    'slug' => Str::slug($validated['name']),
                    'business_id' => $user->ownerBusinessType->id,
                ]);
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
     * @throws \Exception If the transaction fails.
     */
    public function update(array $validated, User $user, EcommerceMedicationType $medication_type): ?bool
    {
        try {
            // Start a database transaction
            return DB::transaction(function () use ($validated, $user, $medication_type) {
                return $medication_type->update([
                    ...$validated,
                    'status' =>  $validated['status'] ?? StatusEnum::APPROVED->value,
                    'active' => $validated['active'] ?? false,
                    'slug' => Str::slug($validated['name'] ?? $medication_type->name),
                    'updated_by_id' => $user->id
                ]);
            });
        } catch (Exception $e) {
            throw new Exception('Failed to update the medication type: ' . $e->getMessage());
        }
    }
}
