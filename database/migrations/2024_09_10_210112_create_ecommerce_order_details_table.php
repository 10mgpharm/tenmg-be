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
        Schema::create('ecommerce_order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecommerce_order_id')->constrained('ecommerce_orders')->cascadeOnDelete()->index();
            $table->foreignId('ecommerce_product_id')->constrained('ecommerce_products')->cascadeOnDelete()->index();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete()->index();

            $table->decimal('actual_price', 18, 2);
            $table->decimal('discount_price', 18, 2)->nullable();
            $table->decimal('tenmg_commission', 18, 2);
            $table->integer('quantity');

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_order_details');
    }
};
