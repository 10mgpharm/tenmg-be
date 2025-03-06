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
        Schema::table('credit_disbursements', function (Blueprint $table) {
            if (!Schema::hasColumn('credit_disbursements', 'lender_id')) {
                $table->foreignId('lender_id')->constrained('businesses')->after('business_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_disbursements', function (Blueprint $table) {
            $table->dropForeign(['lender_id']);
            $table->dropColumn('lender_id');
        });
    }
};
