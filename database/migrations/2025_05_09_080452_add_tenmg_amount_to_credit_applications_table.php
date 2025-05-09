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
        Schema::table('credit_applications', function (Blueprint $table) {
            $table->decimal('tenmg_amount', 15, 2)->nullable()->after('tenmg_interest');
            $table->decimal('actual_interest', 15, 2)->nullable()->after('tenmg_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_applications', function (Blueprint $table) {
            $table->dropColumn('tenmg_amount');
            $table->dropColumn('actual_interest');
        });
    }
};
