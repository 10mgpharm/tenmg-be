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
        Schema::create('credit_lenders_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lender_id')->constrained('businesses')->cascadeOnDelete();
            $table->json('loan_tenure');
            $table->json('loan_interest');
            $table->json('credit_score_category');
            $table->boolean('auto_accept')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_lenders_preferences');
    }
};
