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
        Schema::table('lender_matches', function (Blueprint $table) {
            $table->foreignId('mono_customer_id')
                ->nullable()
                ->after('borrower_reference')
                ->constrained('mono_customers')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lender_matches', function (Blueprint $table) {
            $table->dropForeign(['mono_customer_id']);
            $table->dropColumn('mono_customer_id');
        });
    }
};
