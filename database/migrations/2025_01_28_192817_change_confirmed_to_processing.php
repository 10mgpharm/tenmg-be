<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->enum('status_temp', ['PENDING', 'PROCESSING', 'SHIPPED', 'DELIVERED', 'CANCELED', 'CART', 'COMPLETED'])->nullable()->after('status');
        });

        DB::table('ecommerce_orders')->update([
            'status_temp' => DB::raw("CASE
                WHEN status = 'CONFIRMED' THEN 'PROCESSING'
                ELSE status
            END")
        ]);

        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->renameColumn('status_temp', 'status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback steps (optional, for reversing the migration)
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->enum('status_temp', ['PENDING', 'CONFIRMED', 'SHIPPED', 'DELIVERED', 'CANCELED', 'CART', 'COMPLETED'])->nullable()->after('status');
        });

        // Copy data back to the old column, updating 'PROCESSING' to 'CONFIRMED'
        DB::table('ecommerce_orders')->update([
            'status_temp' => DB::raw("CASE
                WHEN status = 'PROCESSING' THEN 'CONFIRMED'
                ELSE status
            END")
        ]);

        // Drop the new column
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // Rename the old column back to the original name
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->renameColumn('status_temp', 'status');
        });
    }
};
