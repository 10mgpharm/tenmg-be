<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ecommerce_medication_variations', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique('ecommerce_variation_unique');

            // Add the new unique constraint
            $table->unique(
                [
                    'ecommerce_presentation_id',
                    'ecommerce_medication_type_id',
                    'ecommerce_measurement_id',
                    'strength_value',
                    'package_per_roll',
                    'business_id',
                    'weight',
                ],
                'ecommerce_variation_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_medication_variations', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique('ecommerce_variation_unique');

            // Restore the original unique constraint (adjust fields as necessary)
            $table->unique(
                [
                    'ecommerce_presentation_id',
                    'ecommerce_medication_type_id',
                    'ecommerce_measurement_id',
                    'strength_value',
                ],
                'ecommerce_variation_unique'
            );
        });
    }
};
