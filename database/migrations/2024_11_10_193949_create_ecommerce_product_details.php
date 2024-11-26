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
        Schema::create('ecommerce_product_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->foreignId('ecommerce_product_id')->nullable()->constrained('ecommerce_products')->cascadeOnDelete();
            $table->text('essential')->nullable();
            $table->unsignedInteger('starting_stock')->nullable();
            $table->unsignedInteger('current_stock')->nullable();
            $table->enum('stock_status', ['AVAILABLE', 'UNAVAILABLE'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_product_details');
    }
};
