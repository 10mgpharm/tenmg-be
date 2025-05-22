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
        if (!Schema::hasColumn('credit_applications', 'tenmg_interest')) {
            Schema::table('credit_applications', function (Blueprint $table) {
                $table->decimal('tenmg_interest', 10, 2)->nullable()->after('interest_rate');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('credit_applications', 'tenmg_interest')) {
            Schema::table('credit_applications', function (Blueprint $table) {
                $table->dropColumn('tenmg_interest');
            });
        }
    }
};
