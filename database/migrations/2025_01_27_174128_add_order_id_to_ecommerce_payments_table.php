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
        Schema::table('ecommerce_payments', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->constrained('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_payments', function (Blueprint $table) {
            if (Schema::hasColumn('ecommerce_payments', 'order_id')) {
                $table->dropForeign('ecommerce_payments_order_id_foreign');
                $table->dropColumn('order_id');
            }

        });
    }
};
