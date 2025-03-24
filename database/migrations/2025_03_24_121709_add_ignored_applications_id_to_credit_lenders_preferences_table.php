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
        Schema::table('credit_lenders_preferences', function (Blueprint $table) {
            $table->json('ignored_applications_id')->nullable()->after('lender_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_lenders_preferences', function (Blueprint $table) {
            $table->dropColumn('ignored_applications_id');
        });
    }
};
