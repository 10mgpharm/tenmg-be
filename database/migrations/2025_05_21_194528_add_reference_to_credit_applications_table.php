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

        if (!Schema::hasColumn('credit_applications', 'reference')) {
            Schema::table('credit_applications', function (Blueprint $table) {
                $table->string('reference')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('credit_applications', 'reference')) {
            Schema::table('credit_applications', function (Blueprint $table) {
                $table->dropColumn('reference');
            });
        }
    }
};
