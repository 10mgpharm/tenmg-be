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
            $table->bigInteger('ecommerce_measurement_id')->after('ecommerce_presentation_id')->nullable();
            $table->unique(['ecommerce_medication_type_id', 'ecommerce_presentation_id', 'ecommerce_measurement_id', 'strength_value'], 'ecommerce_variation_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_medication_variations', function (Blueprint $table) {
            $table->dropColumn('ecommerce_measurement_id');
        });
    }
};
