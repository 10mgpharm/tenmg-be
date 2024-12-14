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
            if(Schema::hasColumn('ecommerce_medication_variations', 'weight')){
                $table->decimal('weight')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_medication_variations', function (Blueprint $table) {
            //
        });
    }
};
