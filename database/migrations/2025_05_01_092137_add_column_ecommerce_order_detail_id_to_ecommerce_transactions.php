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
        Schema::table('ecommerce_transactions', function (Blueprint $table) {
            if(!Schema::hasColumn('ecommerce_transactions', 'ecommerce_order_detail_id')){
                $table->foreignId('ecommerce_order_detail_id')->nullable()->after('ecommerce_order_id')->constrained('ecommerce_order_details')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_transactions', function (Blueprint $table) {
            if(Schema::hasColumn('ecommerce_transactions', 'ecommerce_order_detail_id')) {
                $table->dropForeign(['ecommerce_order_detail_id']);
                $table->dropColumn('ecommerce_order_detail_id');
            }
        });
    }
};
