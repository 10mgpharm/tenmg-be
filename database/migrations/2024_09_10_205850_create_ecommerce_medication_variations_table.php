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
        Schema::create('ecommerce_medication_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->nullOnDelete()->index();
            $table->foreignId('created_by_id')->constrained('users')->nullOnDelete()->index();
            $table->foreignId('updated_by_id')->constrained('users')->nullOnDelete()->index();
            $table->foreignId('ecommerce_medication_type_id')->constrained('ecommerce_medication_types')->cascadeOnDelete()->index();

            $table->integer('strength_value');
            $table->string('strength', 50); // enum if strength (e.g., mg, mL)
            $table->string('presentation', 100);

            $table->integer('package_per_roll');
            $table->text('description')->nullable();
            $table->integer('weight');
            
            $table->softDeletes();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_medication_variations');
    }
};
