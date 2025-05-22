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
        if (! Schema::hasColumn('ecommerce_products', 'commission')) {
            Schema::table('ecommerce_products', function (Blueprint $table) {
                $table->decimal('commission', 18)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('ecommerce_products', 'commission')) {
            Schema::table('ecommerce_products', function (Blueprint $table) {
                $table->dropColumn('commission');
            });
        }
    }
};
