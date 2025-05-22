<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reverse the JSON fields to string.
     */
    public function up(): void
    {
        Schema::table('credit_lenders_preferences', function (Blueprint $table) {
            $table->json('loan_tenure')->change();
            $table->json('credit_score_category')->change();
            $table->decimal('loan_interest')->change();
        });
    }

    /**
     * Restore JSON fields.
     */
    public function down(): void
    {
        Schema::table('credit_lenders_preferences', function (Blueprint $table) {
            $table->text('loan_tenure')->change();
            $table->text('credit_score_category')->change();
            $table->json('loan_interest')->change();
        });
    }
};
