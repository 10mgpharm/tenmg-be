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
        if(!Schema::hasTable('ecommerce_product_ratings')) {
            Schema::create('ecommerce_product_ratings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ecommerce_product_id')->nullable()->constrained('ecommerce_products')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedTinyInteger('rating')->default(0)->check('rating BETWEEN 0 AND 5');
                $table->timestamps();

                $table->unique(['ecommerce_product_id', 'user_id',]);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_product_ratings');
    }
};
