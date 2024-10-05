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
        Schema::create('ecommerce_medication_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->nullOnDelete()->index();
            $table->foreignId('created_by_id')->constrained('users')->nullOnDelete()->index();
            $table->foreignId('updated_by_id')->constrained('users')->nullOnDelete()->index();

            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'ARCHIVED'])->default('ACTIVE');
            
            $table->softDeletes();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_medication_types');
    }
};
