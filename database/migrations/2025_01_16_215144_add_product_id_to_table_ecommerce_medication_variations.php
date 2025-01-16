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
            if (! Schema::hasColumn('ecommerce_medication_variations', 'ecommerce_product_id')) {
                $table->foreignId('ecommerce_product_id')->nullable()->constrained('ecommerce_products')->cascadeOnDelete()->after('ecommerce_measurement_id');

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
                        'ecommerce_product_id'
                    ],
                    'ecommerce_variation_unique'
                );
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_medication_variations', function (Blueprint $table) {
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
};
