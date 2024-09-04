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
        Schema::table('credit_business_rules', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained('credit_evaluation_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_business_rules', function (Blueprint $table) {
            $table->dropColumn('category_id');
        });
    }
};
