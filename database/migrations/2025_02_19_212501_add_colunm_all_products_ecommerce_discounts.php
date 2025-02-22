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
        if(!Schema::hasColumn('ecommerce_discounts', 'all_products')) {
        Schema::table('ecommerce_discounts', function (Blueprint $table) {
                $table->boolean('all_products')->default(false)->nullable()->after('applicable_products');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_discounts', function (Blueprint $table) {
            //
        });
    }
};
