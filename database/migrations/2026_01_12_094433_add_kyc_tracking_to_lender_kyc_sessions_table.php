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
        Schema::table('lender_kyc_sessions', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('status')->comment('When the tier was successfully completed');
            $table->timestamp('verified_at')->nullable()->after('completed_at')->comment('When verification was confirmed');
            $table->string('completed_tier')->nullable()->after('verified_at')->comment('Track which tier was completed in this session (tier_1, tier_2, tier_3)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lender_kyc_sessions', function (Blueprint $table) {
            $table->dropColumn(['completed_at', 'verified_at', 'completed_tier']);
        });
    }
};
