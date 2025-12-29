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
        // Only run if table exists (in case migration hasn't been run yet)
        if (Schema::hasTable('mono_customers')) {
            Schema::table('mono_customers', function (Blueprint $table) {
                // Drop unique constraint first
                $table->dropUnique(['mono_customer_id']);
            });

            // Use DB facade to alter column (Laravel doesn't support nullable change directly)
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `mono_customers` MODIFY `mono_customer_id` VARCHAR(255) NULL');

            Schema::table('mono_customers', function (Blueprint $table) {
                // Re-add unique constraint (allows multiple NULLs)
                $table->unique('mono_customer_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mono_customers')) {
            Schema::table('mono_customers', function (Blueprint $table) {
                // Drop unique constraint
                $table->dropUnique(['mono_customer_id']);
            });

            // Make NOT NULL again
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `mono_customers` MODIFY `mono_customer_id` VARCHAR(255) NOT NULL');

            Schema::table('mono_customers', function (Blueprint $table) {
                // Re-add unique constraint
                $table->unique('mono_customer_id');
            });
        }
    }
};
