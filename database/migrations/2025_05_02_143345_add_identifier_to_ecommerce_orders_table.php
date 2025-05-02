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

        if (!Schema::hasColumn('ecommerce_orders', 'identifier')) {
            Schema::table('ecommerce_orders', function (Blueprint $table) {
                $table->string('identifier')->nullable()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        if (Schema::hasColumn('ecommerce_orders', 'identifier')) {
            Schema::table('ecommerce_orders', function (Blueprint $table) {
                $table->dropColumn('identifier');
            });
        }
    }
};
