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
            if (Schema::hasColumn('ecommerce_medication_variations', 'package_per_roll')) {
                $table->string('package_per_roll')->nullable()->change();
            }
            if (Schema::hasColumn('ecommerce_medication_variations', 'strength_value')) {
                $table->string('strength_value')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_medication_variations', function (Blueprint $table) {
            // generate down method
            if (Schema::hasColumn('ecommerce_medication_variations', 'package_per_roll')) {
                $table->integer('package_per_roll')->nullable()->change();
            }
            if (Schema::hasColumn('ecommerce_medication_variations', 'strength_value')) {
                $table->integer('strength_value')->nullable()->change();
            }
        });
    }
};
