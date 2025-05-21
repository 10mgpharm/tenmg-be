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

        if (!Schema::hasColumn('ecommerce_order_details', 'discount_code')) {
            Schema::table('ecommerce_order_details', function (Blueprint $table) {
                $table->string('discount_code')->nullable();
            });
        }

        if (!Schema::hasColumn('ecommerce_order_details', 'discount_type')) {
            Schema::table('ecommerce_order_details', function (Blueprint $table) {
                $table->string('discount_type')->nullable();
            });
        }

        if (!Schema::hasColumn('ecommerce_order_details', 'discount_value')) {
            Schema::table('ecommerce_order_details', function (Blueprint $table) {
                $table->string('discount_value')->nullable();
            });
        }

        if (!Schema::hasColumn('ecommerce_order_details', 'discount_expiration_date')) {
            Schema::table('ecommerce_order_details', function (Blueprint $table) {
                $table->string('discount_expiration_date')->nullable();
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_order_details', function (Blueprint $table) {
            if(Schema::hasColumn('ecommerce_order_details', 'discount_code')) {
                $table->dropColumn('discount_code');
                $table->dropColumn('discount_type');
                $table->dropColumn('discount_value');//this is the amount deducted not in percent
                $table->dropColumn('discount_expiration_date');
            }
        });

    }
};
