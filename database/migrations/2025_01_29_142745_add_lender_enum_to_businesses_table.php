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
        Schema::table('businesses', function (Blueprint $table) {
            $table->enum('type_temp', ['ADMIN', 'SUPPLIER', 'VENDOR', 'CUSTOMER_PHARMACY', 'LENDER'])->nullable()->after('type');
        });

        DB::table('businesses')->update(['type_temp' => DB::raw('type')]);

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->renameColumn('type_temp', 'type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->enum('type_temp', ['ADMIN', 'SUPPLIER', 'VENDOR', 'CUSTOMER_PHARMACY'])->nullable()->after('type');
        });

        DB::table('businesses')->update(['type_temp' => DB::raw('type')]);

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->renameColumn('type_temp', 'type');
        });
    }
};
