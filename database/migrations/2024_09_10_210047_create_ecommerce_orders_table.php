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
        Schema::create('ecommerce_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete()->index();
            $table->foreignId('ecommerce_payment_method_id')->nullable()->constrained('ecommerce_payment_methods')->nullOnDelete();
            $table->integer('qty_total');
            $table->decimal('order_total', 18, 2);
            $table->decimal('grand_total', 18, 2);
            $table->decimal('logistic_total', 18, 2)->nullable();
            $table->bigInteger('total_weight')->nullable();
            $table->text('delivery_address');
            $table->enum('delivery_type', ['STANDARD', 'EXPRESS'])->default('STANDARD');
            $table->enum('status', ['PENDING', 'CONFIRMED', 'SHIPPED', 'DELIVERED', 'CANCELED'])->default('PENDING');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_orders');
    }
};
