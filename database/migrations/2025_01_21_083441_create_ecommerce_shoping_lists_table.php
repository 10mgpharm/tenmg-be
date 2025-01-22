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
        Schema::create('ecommerce_shoping_lists', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->string('brand_name');
            $table->date('purchase_date')->nullable();
            $table->integer('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shopping_list_image_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('ecommerce_products')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_shoping_lists');
    }
};
