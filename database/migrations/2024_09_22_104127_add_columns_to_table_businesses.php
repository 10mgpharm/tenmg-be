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
        if (! Schema::hasColumns('businesses', ['license_number', 'expiry_date'])) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->string('license_number')->nullable()->after('status');
                $table->date('expiry_date')->nullable()->after('license_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumns('businesses', ['license_number', 'expiry_date'])) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->dropColumn(['license_number', 'expiry_date']);
            });
        }
    }
};
