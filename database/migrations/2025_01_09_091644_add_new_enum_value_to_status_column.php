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

        // Add the new enum column temporarily with the updated values
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->enum('status_temp', ['PENDING','CONFIRMED','SHIPPED','DELIVERED','CANCELED', 'CART'])->nullable();
        });

        // Copy the data from the old column to the new one (if necessary)
        DB::table('ecommerce_orders')->update([
            'status_temp' => DB::raw('`status`')
        ]);

        // Drop the old column
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // Rename the new column to the old column name
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->renameColumn('status_temp', 'status');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        // Revert the enum column back to the old values (without the new one)
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->enum('status', ['PENDING','CONFIRMED','SHIPPED','DELIVERED','CANCELED'])->nullable();
        });

        // Copy data back from the new column to the old column (if necessary)
        DB::table('ecommerce_orders')->update([
            'status' => DB::raw('`status_temp`')
        ]);

        // Drop the new column
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropColumn('status_temp');
        });
    }
};
