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
        Schema::table('ecommerce_order_details', function (Blueprint $table) {
            $table->decimal('tenmg_commission_percent', 8,2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_order_details', function (Blueprint $table) {
            $table->dropColumn('tenmg_commission_percent');
        });
    }
};
