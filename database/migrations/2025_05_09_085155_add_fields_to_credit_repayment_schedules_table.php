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
        Schema::table('credit_repayment_schedules', function (Blueprint $table) {
            $table->decimal('tenmg_interest', 15, 2)->after('interest');
            $table->decimal('actual_interest', 15, 2)->after('tenmg_interest');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_repayment_schedules', function (Blueprint $table) {
            $table->dropColumn('tenmg_interest');
        });
    }
};
