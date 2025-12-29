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
        Schema::create('lender_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->decimal('rate', 5, 2)->comment('Interest rate percentage (max 9%)');
            $table->text('instruction')->nullable()->comment('Lender instruction on how funds should be disbursed');
            $table->json('instruction_config')->nullable()->comment('Structured config for advanced disbursement rules');
            $table->timestamps();

            $table->unique('business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lender_settings');
    }
};
