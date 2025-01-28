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
        Schema::table('ecommerce_discounts', function (Blueprint $table) {
            if(!Schema::hasColumn('ecommerce_discounts', 'status')) {
                $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->after('customer_limit')->nullable();
            }
        });
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
