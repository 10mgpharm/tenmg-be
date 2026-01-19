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
        Schema::table('mono_mandates', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['mono_customer_id']);

            // Change mono_customer_id to string to store Mono API customer ID
            $table->string('mono_customer_id', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mono_mandates', function (Blueprint $table) {
            // Revert back to foreignId
            $table->foreignId('mono_customer_id')
                ->nullable()
                ->constrained('mono_customers')
                ->nullOnDelete()
                ->change();
        });
    }
};
