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
        Schema::table('businesses', function (Blueprint $table) {
            $table->enum('highest_completed_kyc_tier', ['tier_1', 'tier_2', 'tier_3'])
                ->nullable()
                ->after('lender_type')
                ->comment('Track highest completed KYC tier per lender business');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('highest_completed_kyc_tier');
        });
    }
};
