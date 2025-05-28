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
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            if(!Schema::hasColumn('ecommerce_orders', 'shipping_fee')) {
                $table->decimal('shipping_fee', 18, 2)->default(0)->after('grand_total')->comment('Shipping fee for the order');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            if(Schema::hasColumn('ecommerce_orders', 'shipping_fee')) {
                $table->dropColumn('shipping_fee');
            }
        });
    }
};
