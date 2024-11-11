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
        Schema::create('ecommerce_products', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();

            $table->foreignId('ecommerce_brand_id')->nullable()->constrained('ecommerce_brands')->nullOnDelete();
            $table->foreignId('ecommerce_category_id')->nullable()->constrained('ecommerce_categories')->nullOnDelete();

            $table->foreignId('thumbnail_file_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->foreignId('ecommerce_medication_type_id')->constrained('ecommerce_medication_types')->cascadeOnDelete()->name('fk_product_med_type_id');
            $table->foreignId('ecommerce_variation_id')->constrained('ecommerce_medication_variations')->cascadeOnDelete();

            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->integer('quantity');
            $table->decimal('actual_price', 18, 2);
            $table->decimal('discount_price', 18, 2)->nullable();
            $table->integer('min_delivery_duration');
            $table->integer('max_delivery_duration');

            $table->timestamp('expired_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_products');
    }
};
