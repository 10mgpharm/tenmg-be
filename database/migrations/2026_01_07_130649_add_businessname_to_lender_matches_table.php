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
            $table->string('businessname')->nullable()->after('borrower_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lender_matches', function (Blueprint $table) {
            $table->dropColumn('businessname');
        });
    }
};
